<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\IpUtils;

/**
 * يُعيد كتابة REMOTE_ADDR من الـ header `CF-Connecting-IP` عندما يصل الطلب
 * من Cloudflare. هذه الـ header تُضاف حصراً بواسطة Cloudflare ولا يمكن
 * spoofing لأن CF يُزيل أيّ قيمة مُرسَلة من العميل قبل إضافتها بنفسه.
 *
 * لماذا ليس TrustProxies وحده؟
 *   - Symfony يستخدم X-Forwarded-For (عرضة لـ spoofing لو وُضعت قائمة
 *     trusted أوسع من اللازم).
 *   - CF-Connecting-IP خاص بـ Cloudflare ولا توجد chains متعدّدة فيه.
 *
 * الميدلوير يعمل فقط إذا:
 *   1) TRUSTED_PROXIES_CLOUDFLARE=true.
 *   2) الطلب قادم من شبكات Cloudflare المعلومة (IpUtils::checkIp ضدّ
 *      قائمة IPs/CIDR رسمية منشورة من Cloudflare).
 *   3) القيمة validIP (IPv4 أو IPv6 صالحة).
 *
 * ⚠️ تحذير أمني حاسم:
 * عند تفعيل هذا الميدلوير، يجب أن يكون الخادم **خلف Cloudflare حصراً**،
 * أي أن firewall يمنع الاتصالات المباشرة على 80/443. وإلا، أيّ مهاجم
 * يستطيع ضرب الـ origin مباشرة وحقن CF-Connecting-IP مزيّف بدلاً من IPه
 * الحقيقي، متجاوزاً rate limiting و IP allowlists.
 *
 * ترتيب الميدلوير مهم جداً:
 *   يجب أن يكون أول global middleware (نستخدم prepend في bootstrap/app.php)
 *   حتى يُعدّل REMOTE_ADDR قبل أي auth/rate-limit/logging.
 *
 * Trust chain:
 *   Cloudflare edge → [CF يمحو أي CF-Connecting-IP مُرسلة ويُعيد كتابتها]
 *                  → origin server → [CloudflareRealIp يفحص REMOTE_ADDR في
 *                    شبكات CF] → يُعيد كتابة REMOTE_ADDR → TrustProxies
 *                  → RateLimiter/Analytics يرى IP الزائر الحقيقي.
 */
final class CloudflareRealIp
{
    /**
     * قائمة الـ CIDR المنشورة من Cloudflare — راجعها دورياً.
     * المصدر:
     *   https://www.cloudflare.com/ips-v4
     *   https://www.cloudflare.com/ips-v6
     */
    private const CLOUDFLARE_RANGES = [
        // IPv4
        '173.245.48.0/20',
        '103.21.244.0/22',
        '103.22.200.0/22',
        '103.31.4.0/22',
        '141.101.64.0/18',
        '108.162.192.0/18',
        '190.93.240.0/20',
        '188.114.96.0/20',
        '197.234.240.0/22',
        '198.41.128.0/17',
        '162.158.0.0/15',
        '104.16.0.0/13',
        '104.24.0.0/14',
        '172.64.0.0/13',
        '131.0.72.0/22',
        // IPv6
        '2400:cb00::/32',
        '2606:4700::/32',
        '2803:f800::/32',
        '2405:b500::/32',
        '2405:8100::/32',
        '2a06:98c0::/29',
        '2c0f:f248::/32',
    ];

    /**
     * كاش IP→boolean لفحص CF ranges.
     * Symfony IpUtils يحتفظ بكاش داخلي مماثل، لكن نُضيف طبقة على مستوى الـ
     * middleware لتوضيح النية وتجنّب الاعتماد على تفاصيل implementation.
     *
     * ⚠️ مهم في بيئات long-running (Octane / RoadRunner / Swoole):
     * الـ static arrays لا تُفرَّغ بين requests، لذلك نفرض حدّاً أعلى
     * (MAX_CACHE_SIZE) لتجنّب memory leak تراكمي على مدى أيام التشغيل.
     *
     * @var array<string, bool>
     */
    private static array $rangeCache = [];

    /** الحدّ الأعلى لعدد الإدخالات قبل إفراغ الكاش (للبيئات طويلة العمر). */
    private const MAX_CACHE_SIZE = 1024;

    public function handle(Request $request, Closure $next)
    {
        if (! (bool) config('services.trusted.cloudflare', false)) {
            return $next($request);
        }

        $remoteAddr = $request->server('REMOTE_ADDR');
        $cfIpRaw    = $request->header('CF-Connecting-IP');

        if ($cfIpRaw === null || $remoteAddr === null) {
            return $next($request);
        }

        // 1) Normalization: trim + تحويل لصيغة موحّدة (canonical form).
        //    inet_pton + inet_ntop يوحّدان:
        //      - IPv6 compressed (::1) → form canonical
        //      - IPv4-mapped IPv6 (::ffff:1.2.3.4) → يُبقى كما هو
        //    الفائدة: cache hit أعلى + قرارات checkIp ثابتة.
        $cfIp = $this->normalizeIp(trim($cfIpRaw));

        if ($cfIp === null) {
            return $next($request);
        }

        // 2) تطبيع edge IP. إذا فشل (malformed/غير صالح) نرفض بدون fallback —
        //    ضمان سلوك deterministic ومنع أي تفسير غامض.
        $remoteAddrNormalized = $this->normalizeIp(trim($remoteAddr));

        if ($remoteAddrNormalized === null) {
            return $next($request);
        }

        if (! $this->isFromCloudflare($remoteAddrNormalized)) {
            return $next($request);
        }

        // 3) نُعيد كتابة REMOTE_ADDR ليُعامَل في كل الطبقات اللاحقة كأنه IP
        //    الزائر الحقيقي (TrustProxies، RateLimiter، Analytics، Logs).
        $request->server->set('REMOTE_ADDR', $cfIp);

        // 4) Security hardening: نحذف الـ header بعد استخدامها حتى لا تتسرّب
        //    إلى middleware/logs لاحقة قد تستخدمها بشكل خاطئ كمصدر موثوق
        //    دون التحقق من edge IP.
        $request->headers->remove('CF-Connecting-IP');

        return $next($request);
    }

    /**
     * تطبيع + validate عنوان IP. يُرجع null إذا كانت القيمة غير صالحة.
     */
    private function normalizeIp(string $ip): ?string
    {
        if (! filter_var($ip, FILTER_VALIDATE_IP)) {
            return null;
        }

        $packed = @inet_pton($ip);
        if ($packed === false) {
            return null;
        }

        $canonical = @inet_ntop($packed);

        return $canonical !== false ? $canonical : $ip;
    }

    /**
     * فحص مُكاش لمعرفة ما إذا كان IP ضمن شبكات Cloudflare، مع حدّ أعلى للحجم.
     */
    private function isFromCloudflare(string $ip): bool
    {
        if (isset(self::$rangeCache[$ip])) {
            return self::$rangeCache[$ip];
        }

        // حماية من نمو غير محدود في long-running workers.
        if (count(self::$rangeCache) >= self::MAX_CACHE_SIZE) {
            self::$rangeCache = [];
        }

        return self::$rangeCache[$ip] = IpUtils::checkIp($ip, self::CLOUDFLARE_RANGES);
    }
}
