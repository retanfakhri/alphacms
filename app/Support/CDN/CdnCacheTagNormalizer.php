<?php

declare(strict_types=1);

namespace App\Support\CDN;

/**
 * تطبيع وسوم ‎Cache-Tag‎ لاستجابات HTML (Frontpage) ولـ ‎CDNPurgeBuffer‎ بنفس القواعد
 * — يجب أن تتطابق عناوين الـ cache على الـ edge مع طلب الـ purge وإلا لن تُنزع العُقد.
 *
 * ‎- تصفية ‎+ إزالة تكرار + ترتيب ثابت
 * ‎- بادئة ‎FRONTPAGE_CDN_CACHE_TAG_NAMESPACE‎ (مثال ‎app → app:news‎) مع حماية ‎app:app:…‎
 * ‎- دمج ‎news‎ مع ‎app:news‎ إلى وسم واحد بعد البادئة
 */
final class CdnCacheTagNormalizer
{
    /**
     * @param  array<int|string|null>  $tags
     * @return array<int, string>
     */
    public static function normalize(array $tags): array
    {
        $filtered = array_values(array_unique(
            array_filter(
                $tags,
                static fn ($t): bool => is_string($t) && $t !== '',
            ),
            SORT_STRING,
        ));

        sort($filtered, SORT_STRING);

        $ns = trim((string) config('frontpage.cdn_cache_tag_namespace', ''));
        if ($ns !== '') {
            $ns = rtrim($ns, ':');
            $prefixed = [];
            foreach ($filtered as $t) {
                $prefixed[] = str_starts_with($t, $ns.':') ? $t : $ns.':'.$t;
            }
            $filtered = array_values(array_unique($prefixed, SORT_STRING));
            sort($filtered, SORT_STRING);
        }

        return $filtered;
    }
}
