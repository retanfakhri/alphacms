<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\SettingAsset;
use App\Settings\CDNSettings;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

// Temporary fix until composer dump-autoload is run
if (! function_exists('cache_key')) {
    require_once app_path('Support/helpers.php');
}

class SettingsManagerService
{
    private const CACHE_PREFIX = 'settings';

    /**
     * TTL for settings group cache: [fresh, stale] for Cache::flexible().
     * Settings change rarely; fresh window is generous to minimize DB hits.
     */
    private const CACHE_TTL = [300, 1800];

    // ─── Helpers ────────────────────────────────────────────────────────

    /**
     * Load an entire settings group in a single query and cache it.
     */
    private function getGroup(string $group): array
    {
        $key = cache_key(self::CACHE_PREFIX.":group:{$group}");

        $callback = fn () => DB::table('settings')
            ->where('group', $group)
            ->pluck('payload', 'name')
            ->map(function ($payload) {
                $decoded = json_decode((string) $payload, true);

                return json_last_error() === JSON_ERROR_NONE ? $decoded : null;
            })
            ->all();

        if (Cache::supportsTags()) {
            return Cache::tags([self::CACHE_PREFIX, self::CACHE_PREFIX.":{$group}"])
                ->flexible($key, self::CACHE_TTL, $callback, ['seconds' => 5]);
        }

        return Cache::memo()->flexible($key, self::CACHE_TTL, $callback, ['seconds' => 5]);
    }

    private function getValue(string $group, string $name, mixed $default = null): mixed
    {
        $values = $this->getGroup($group);

        if (! array_key_exists($name, $values)) {
            return $default;
        }

        return $values[$name] ?? $default;
    }

    private function setValues(string $group, array $values): void
    {
        $now = now();

        $rows = [];
        foreach ($values as $name => $value) {
            $rows[] = [
                'group' => $group,
                'name' => $name,
                'locked' => false,
                'payload' => json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                'updated_at' => $now,
                'created_at' => $now,
            ];
        }

        DB::table('settings')->upsert($rows, ['group', 'name'], ['payload', 'locked', 'updated_at']);

        $this->invalidateGroup($group);
    }

    /**
     * Invalidate the cached copy of a settings group after mutation.
     */
    private function invalidateGroup(string $group): void
    {
        Cache::forget(cache_key(self::CACHE_PREFIX.":group:{$group}"));

        if (Cache::supportsTags()) {
            Cache::tags([self::CACHE_PREFIX.":{$group}"])->flush();
        }
    }

    /**
     * Flush every cached settings group. Useful after bulk restores or imports.
     */
    public function flushCache(): void
    {
        if (Cache::supportsTags()) {
            Cache::tags([self::CACHE_PREFIX])->flush();

            return;
        }

        foreach ([
            'general', 'smtp', 'social', 'tracking',
            'third_party', 'third_party_social_login', 'third_party_recaptcha',
            'third_party_firebase', 'third_party_google_maps', 'third_party_ai',
            'third_party_openai', 'third_party_gemini', 'third_party_whatsapp',
            'third_party_app_links',
        ] as $group) {
            Cache::forget(cache_key(self::CACHE_PREFIX.":group:{$group}"));
        }
    }

    private function nullable(mixed $value): ?string
    {
        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }

    private function nullableInt(mixed $value): ?int
    {
        if ($value === null || $value === '' || $value === 'null') {
            return null;
        }

        return (int) $value;
    }

    // ─── General ───────────────────────────────────────────────────────

    public function getGeneral(): array
    {
        return [
            'site_name' => $this->getValue('general', 'site_name', (string) config('app.name', 'AlphaMedia')),
            'site_email' => $this->getValue('general', 'site_email', ''),
            'site_phone' => $this->getValue('general', 'site_phone', ''),
            'site_phone_country_code' => $this->getValue('general', 'site_phone_country_code', '+962'),
            'site_phone_country_flag' => $this->getValue('general', 'site_phone_country_flag', '🇯🇴'),
            'site_url' => $this->getValue('general', 'site_url', (string) config('app.url', 'http://localhost')),
            'timezone' => $this->getValue('general', 'timezone', (string) config('app.timezone', 'UTC')),
            'copyright_text' => $this->getValue('general', 'copyright_text', ''),
            'footer_attribution' => $this->getValue('general', 'footer_attribution', ''),
            'cookies_text' => $this->getValue('general', 'cookies_text', ''),
            'enable_watermark' => (bool) $this->getValue('general', 'enable_watermark', false),
            'enable_comments' => (bool) $this->getValue('general', 'enable_comments', false),
            'active_theme' => $this->getValue('general', 'active_theme', ''),
        ];
    }

    public function areCommentsEnabledGlobally(): bool
    {
        return (bool) ($this->getGeneral()['enable_comments'] ?? false);
    }

    public function saveGeneral(array $data): void
    {
        $this->setValues('general', [
            'site_name' => (string) $data['site_name'],
            'site_email' => $this->nullable($data['site_email'] ?? null),
            'site_phone' => $this->nullable($data['site_phone'] ?? null),
            'site_phone_country_code' => $this->nullable($data['site_phone_country_code'] ?? '+962') ?: '+962',
            'site_phone_country_flag' => $this->nullable($data['site_phone_country_flag'] ?? '🇯🇴') ?: '🇯🇴',
            'site_url' => (string) $data['site_url'],
            'timezone' => (string) $data['timezone'],
            'copyright_text' => $this->nullable($data['copyright_text'] ?? null),
            'footer_attribution' => $this->nullable($data['footer_attribution'] ?? null),
            'cookies_text' => $this->nullable($data['cookies_text'] ?? null),
            'enable_watermark' => (bool) ($data['enable_watermark'] ?? false),
            'enable_comments' => (bool) ($data['enable_comments'] ?? false),
            'active_theme' => $this->nullable($data['active_theme'] ?? null),
        ]);

        $siteName = (string) $data['site_name'];
        $siteUrl = (string) $data['site_url'];
        $tz = (string) $data['timezone'];

        config()->set('app.name', $siteName);
        config()->set('app.url', $siteUrl);
        config()->set('app.timezone', $tz);

        $this->syncGeneralKeysToEnvFile($siteName, $siteUrl, $tz);
        $this->syncThemeToEnvFile($this->nullable($data['active_theme'] ?? null));
    }

    private function syncThemeToEnvFile(?string $themeSlug): void
    {
        // Finding #9: Disabled runtime .env mutation for better production/container safety.
        return;
    }

    private function syncGeneralKeysToEnvFile(string $name, string $url, string $timezone): void
    {
        // Finding #9: Disabled runtime .env mutation for better production/container safety.
        return;
    }

    private function formatEnvFileValue(string $value): string
    {
        $needsQuotes = $value === ''
            || preg_match('/\R/', $value)
            || strpbrk($value, " \t#\"'\\=") !== false;

        if ($needsQuotes) {
            return '"'.addcslashes($value, '"\\').'"';
        }

        return $value;
    }

    // ─── SMTP ──────────────────────────────────────────────────────────

    public function getSmtp(): array
    {
        $password = $this->getValue('smtp', 'password', null);

        if ($password !== null && $password !== '') {
            try {
                $password = decrypt($password);
            } catch (DecryptException) {
                // Already plain text (legacy)
            }
        }

        return [
            'mailer' => $this->getValue('smtp', 'mailer', (string) env('MAIL_MAILER', 'smtp')),
            'host' => $this->getValue('smtp', 'host', (string) env('MAIL_HOST', '')),
            'port' => (int) $this->getValue('smtp', 'port', (int) env('MAIL_PORT', 587)),
            'encryption' => $this->getValue('smtp', 'encryption', (string) env('MAIL_ENCRYPTION', 'tls')),
            'from_email' => $this->getValue('smtp', 'from_email', (string) env('MAIL_FROM_ADDRESS', '')),
            'username' => $this->getValue('smtp', 'username', (string) env('MAIL_USERNAME', '')),
            'password' => $password ?? (string) env('MAIL_PASSWORD', ''),
            'from_name' => $this->getValue('smtp', 'from_name', (string) env('MAIL_FROM_NAME', 'AlphaMedia')),
        ];
    }

    public function getSmtpSafeForFrontend(): array
    {
        $data = $this->getSmtp();
        $data['has_password'] = ! empty($data['password']);
        $data['password'] = '';

        return $data;
    }

    public function applyMailSmtpConfigFromDatabase(): void
    {
        $data = $this->getSmtp();
        $mailer = (string) ($data['mailer'] ?? '');
        if ($mailer === '') {
            $mailer = (string) env('MAIL_MAILER', 'log');
        }
        config()->set('mail.default', $mailer);
        config()->set('mail.mailers.smtp.host', (string) ($data['host'] ?? ''));
        config()->set('mail.mailers.smtp.port', (int) ($data['port'] ?? 587));
        config()->set('mail.mailers.smtp.encryption', $this->nullable($data['encryption'] ?? null));
        config()->set('mail.mailers.smtp.username', $this->nullable($data['username'] ?? null));
        $pw = (string) ($data['password'] ?? '');
        config()->set('mail.mailers.smtp.password', $pw !== '' ? $pw : null);
        if (trim((string) ($data['from_email'] ?? '')) !== '') {
            config()->set('mail.from.address', (string) $data['from_email']);
        }
        if (trim((string) ($data['from_name'] ?? '')) !== '') {
            config()->set('mail.from.name', (string) $data['from_name']);
        }
    }

    public function saveSmtp(array $data): void
    {
        $password = $this->nullable($data['password'] ?? null);

        $rows = [
            'mailer' => (string) $data['mailer'],
            'host' => (string) $data['host'],
            'port' => (int) $data['port'],
            'encryption' => $this->nullable($data['encryption'] ?? null),
            'from_email' => (string) $data['from_email'],
            'username' => $this->nullable($data['username'] ?? null),
            'from_name' => (string) $data['from_name'],
        ];

        // Finding #12: Only update secret if a new value is provided.
        if ($password !== null) {
            $rows['password'] = encrypt($password);
        }

        $this->setValues('smtp', $rows);

        config()->set('mail.default', (string) $data['mailer']);
        config()->set('mail.mailers.smtp.host', (string) $data['host']);
        config()->set('mail.mailers.smtp.port', (int) $data['port']);
        config()->set('mail.mailers.smtp.encryption', $this->nullable($data['encryption'] ?? null));
        config()->set('mail.mailers.smtp.username', $this->nullable($data['username'] ?? null));
        
        if ($password !== null) {
            config()->set('mail.mailers.smtp.password', $password);
        }
        
        config()->set('mail.from.address', (string) $data['from_email']);
        config()->set('mail.from.name', (string) $data['from_name']);
    }

    // ─── Social ────────────────────────────────────────────────────────

    public function getSocial(): array
    {
        return [
            'facebook' => $this->getValue('social', 'facebook', ''),
            'fb_page_id' => $this->getValue('social', 'fb_page_id', ''),
            'twitter' => $this->getValue('social', 'twitter', ''),
            'instagram' => $this->getValue('social', 'instagram', ''),
            'linkedin' => $this->getValue('social', 'linkedin', ''),
            'youtube' => $this->getValue('social', 'youtube', ''),
            'tiktok' => $this->getValue('social', 'tiktok', ''),
            'whatsapp_number' => $this->getValue('social', 'whatsapp_number', ''),
            'whatsapp_channel' => $this->getValue('social', 'whatsapp_channel', ''),
        ];
    }

    public function saveSocial(array $data): void
    {
        $this->setValues('social', [
            'facebook' => $this->nullable($data['facebook'] ?? null),
            'fb_page_id' => $this->nullable($data['fb_page_id'] ?? null),
            'twitter' => $this->nullable($data['twitter'] ?? null),
            'instagram' => $this->nullable($data['instagram'] ?? null),
            'linkedin' => $this->nullable($data['linkedin'] ?? null),
            'youtube' => $this->nullable($data['youtube'] ?? null),
            'tiktok' => $this->nullable($data['tiktok'] ?? null),
            'whatsapp_number' => $this->nullable($data['whatsapp_number'] ?? null),
            'whatsapp_channel' => $this->nullable($data['whatsapp_channel'] ?? null),
        ]);
    }

    // ─── Tracking ──────────────────────────────────────────────────────

    public function getTracking(): array
    {
        return [
            'google_meta_tag' => $this->getValue('tracking', 'google_meta_tag', ''),
            'facebook_pixel' => $this->getValue('tracking', 'facebook_pixel', ''),
            'facebook_page_id' => $this->getValue('tracking', 'facebook_page_id', ''),
            'google_analytics' => $this->getValue('tracking', 'google_analytics', ''),
            'tiktok_pixel' => $this->getValue('tracking', 'tiktok_pixel', ''),
            'instagram_pixel' => $this->getValue('tracking', 'instagram_pixel', ''),
        ];
    }

    public function saveTracking(array $data): void
    {
        $this->setValues('tracking', [
            'google_meta_tag' => $this->nullable($data['google_meta_tag'] ?? null),
            'facebook_pixel' => $this->nullable($data['facebook_pixel'] ?? null),
            'facebook_page_id' => $this->nullable($data['facebook_page_id'] ?? null),
            'google_analytics' => $this->nullable($data['google_analytics'] ?? null),
            'tiktok_pixel' => $this->nullable($data['tiktok_pixel'] ?? null),
            'instagram_pixel' => $this->nullable($data['instagram_pixel'] ?? null),
        ]);
    }

    // ─── Third Party ───────────────────────────────────────────────────

    public function getThirdParty(): array
    {
        return [
            'third_party_enabled' => (bool) $this->getValue('third_party', 'enabled', (bool) env('THIRD_PARTY_ENABLED', true)),

            'google_enabled' => (bool) $this->getValue('third_party_social_login', 'google_enabled', (bool) env('GOOGLE_ENABLED', false)),
            'google_client_id' => $this->getValue('third_party_social_login', 'google_client_id', (string) env('GOOGLE_CLIENT_ID', '')),
            'google_client_secret' => $this->getValue('third_party_social_login', 'google_client_secret', (string) env('GOOGLE_CLIENT_SECRET', '')),
            'google_redirect_url' => $this->getValue('third_party_social_login', 'google_redirect_url', (string) env('GOOGLE_REDIRECT_URI', '')),
            'facebook_enabled' => (bool) $this->getValue('third_party_social_login', 'facebook_enabled', (bool) env('FACEBOOK_ENABLED', false)),
            'facebook_client_id' => $this->getValue('third_party_social_login', 'facebook_client_id', (string) env('FACEBOOK_CLIENT_ID', '')),
            'facebook_client_secret' => $this->getValue('third_party_social_login', 'facebook_client_secret', (string) env('FACEBOOK_CLIENT_SECRET', '')),
            'facebook_redirect_url' => $this->getValue('third_party_social_login', 'facebook_redirect_url', (string) env('FACEBOOK_REDIRECT_URL', '')),

            'captcha_enabled' => (bool) $this->getValue('third_party_recaptcha', 'enabled', (bool) env('CAPTCHA_ENABLED', true)),
            'captcha_version' => $this->getValue('third_party_recaptcha', 'version', (string) env('RECAPTCHA_VERSION', 'v3')),
            'captcha_score' => (float) $this->getValue('third_party_recaptcha', 'score', (float) env('CAPTCHA_SCORE', 0.5)),
            'captcha_site_key' => $this->getValue('third_party_recaptcha', 'site_key', (string) env('CAPTCHA_V3_SITEKEY', '')),
            'captcha_secret_key' => $this->getValue('third_party_recaptcha', 'secret_key', (string) env('CAPTCHA_V3_SECRET', '')),

            'firebase_enabled' => (bool) $this->getValue('third_party_firebase', 'enabled', (bool) env('FIREBASE_ENABLED', false)),
            'firebase_credentials' => $this->getValue('third_party_firebase', 'credentials_path', (string) env('FIREBASE_CREDENTIALS', 'storage/app/firebase-auth.json')),
            'firebase_project' => $this->getValue('third_party_firebase', 'project_id', (string) env('FIREBASE_PROJECT', 'app')),
            'firebase_api_key' => $this->getValue('third_party_firebase', 'api_key', (string) env('FIREBASE_API_KEY', '')),
            'firebase_auth_domain' => $this->getValue('third_party_firebase', 'auth_domain', (string) env('FIREBASE_AUTH_DOMAIN', '')),
            'firebase_database_url' => $this->getValue('third_party_firebase', 'database_url', (string) env('FIREBASE_DATABASE_URL', '')),
            'firebase_storage_bucket' => $this->getValue('third_party_firebase', 'storage_bucket', (string) env('FIREBASE_STORAGE_BUCKET', env('FIREBASE_STORAGE_DEFAULT_BUCKET', ''))),
            'firebase_messaging_sender_id' => $this->getValue('third_party_firebase', 'messaging_sender_id', (string) env('FIREBASE_MESSAGING_SENDER_ID', '')),
            'firebase_app_id' => $this->getValue('third_party_firebase', 'app_id', (string) env('FIREBASE_APP_ID', '')),
            'firebase_measurement_id' => $this->getValue('third_party_firebase', 'measurement_id', (string) env('FIREBASE_MEASUREMENT_ID', '')),
            'firebase_token_uri' => $this->getValue('third_party_firebase', 'token_uri', (string) env('FIREBASE_TOKEN_URI', '')),

            'google_maps_enabled' => (bool) $this->getValue('third_party_google_maps', 'enabled', (bool) env('GOOGLE_MAPS_ENABLED', false)),
            'google_maps_client_key' => $this->getValue('third_party_google_maps', 'client_key', (string) env('GOOGLE_MAPS_CLIENT_KEY', '')),
            'google_maps_server_key' => $this->getValue('third_party_google_maps', 'server_key', (string) env('GOOGLE_MAPS_SERVER_KEY', '')),

            'ai_enabled' => (bool) $this->getValue('third_party_ai', 'enabled', false),
            'ai_provider' => $this->getValue('third_party_ai', 'provider', 'openai'),

            'openai_api_key' => $this->getValue('third_party_openai', 'api_key', (string) env('OPENAI_API_KEY', '')),
            'openai_base_url' => $this->getValue('third_party_openai', 'base_url', (string) env('OPENAI_BASE_URL', 'https://api.openai.com/v1')),
            'openai_model' => $this->getValue('third_party_openai', 'model', (string) env('OPENAI_MODEL', 'gpt-4o-mini')),
            'openai_temperature' => (float) $this->getValue('third_party_openai', 'temperature', (float) env('OPENAI_TEMPERATURE', 0.4)),
            'openai_max_tokens' => $this->nullableInt($this->getValue('third_party_openai', 'max_tokens', env('OPENAI_MAX_TOKENS', null))),
            'openai_request_timeout' => (int) $this->getValue('third_party_openai', 'request_timeout', (int) env('OPENAI_REQUEST_TIMEOUT', 30)),
            'openai_writing_style' => $this->getValue('third_party_openai', 'writing_style', (string) env('OPENAI_WRITING_STYLE', 'journalistic')),
            'openai_news_prompt' => $this->getValue('third_party_openai', 'news_prompt', (string) env('OPENAI_NEWS_PROMPT', '')),
            'openai_article_prompt' => $this->getValue('third_party_openai', 'article_prompt', (string) env('OPENAI_ARTICLE_PROMPT', '')),
            'openai_default_prompt' => $this->getValue('third_party_openai', 'default_prompt', (string) env('OPENAI_DEFAULT_PROMPT', '')),
            'openai_ai_rewrite_prompt' => $this->getValue('third_party_openai', 'ai_rewrite_prompt', null),
            'openai_ai_seo_prompt' => $this->getValue('third_party_openai', 'ai_seo_prompt', null),
            'openai_ai_tags_prompt' => $this->getValue('third_party_openai', 'ai_tags_prompt', null),

            'gemini_api_key' => $this->getValue('third_party_gemini', 'api_key', ''),
            'gemini_model' => $this->getValue('third_party_gemini', 'model', 'gemini-2.5-flash'),
            'gemini_temperature' => (float) $this->getValue('third_party_gemini', 'temperature', 0.4),
            'gemini_max_tokens' => $this->nullableInt($this->getValue('third_party_gemini', 'max_tokens', null)),
            'gemini_request_timeout' => (int) $this->getValue('third_party_gemini', 'request_timeout', 30),
            'gemini_prompt' => $this->getValue('third_party_gemini', 'prompt', ''),

            'whatsapp_enabled' => (bool) $this->getValue('third_party_whatsapp', 'enabled', (bool) env('ULTRAMSG_ENABLED', true)),
            'whatsapp_instance_id' => $this->getValue('third_party_whatsapp', 'instance_id', (string) env('ULTRAMSG_INSTANCE_ID', '')),
            'whatsapp_token' => $this->getValue('third_party_whatsapp', 'token', (string) env('ULTRAMSG_TOKEN', '')),
            'whatsapp_base_url' => $this->getValue('third_party_whatsapp', 'base_url', (string) env('ULTRAMSG_BASE_URL', 'https://api.ultramsg.com')),
            'whatsapp_batch_size' => (int) $this->getValue('third_party_whatsapp', 'batch_size', (int) env('WHATSAPP_BATCH_SIZE', 20)),
            'whatsapp_sleep' => (int) $this->getValue('third_party_whatsapp', 'sleep', (int) env('WHATSAPP_SLEEP', 3)),

            'google_play_url' => $this->getValue('third_party_app_links', 'google_play_url', (string) env('GOOGLE_PLAY_URL', '')),
            'apple_store_url' => $this->getValue('third_party_app_links', 'apple_store_url', (string) env('APPLE_STORE_URL', '')),
        ];
    }

    public function getThirdPartySafeForFrontend(): array
    {
        $data = $this->getThirdParty();

        $secrets = [
            'google_client_secret',
            'facebook_client_secret',
            'captcha_secret_key',
            'firebase_api_key',
            'google_maps_server_key',
            'openai_api_key',
            'gemini_api_key',
            'whatsapp_token',
        ];

        foreach ($secrets as $key) {
            $data['has_'.$key] = ! empty($data[$key]);
            $data[$key] = '';
        }

        return $data;
    }

    public function saveThirdParty(array $data): void
    {
        $groups = [
            'third_party' => [
                'enabled' => (bool) ($data['third_party_enabled'] ?? true),
            ],
            'third_party_social_login' => array_filter([
                'google_enabled' => isset($data['google_enabled']) ? (bool) $data['google_enabled'] : null,
                'google_client_id' => $this->nullable($data['google_client_id'] ?? null),
                'google_client_secret' => $this->nullable($data['google_client_secret'] ?? null),
                'google_redirect_url' => $this->nullable($data['google_redirect_url'] ?? null),
                'facebook_enabled' => isset($data['facebook_enabled']) ? (bool) $data['facebook_enabled'] : null,
                'facebook_client_id' => $this->nullable($data['facebook_client_id'] ?? null),
                'facebook_client_secret' => $this->nullable($data['facebook_client_secret'] ?? null),
                'facebook_redirect_url' => $this->nullable($data['facebook_redirect_url'] ?? null),
            ], fn ($v) => $v !== null),
            'third_party_recaptcha' => array_filter([
                'enabled' => isset($data['captcha_enabled']) ? (bool) $data['captcha_enabled'] : null,
                'version' => $this->nullable($data['captcha_version'] ?? null),
                'score' => isset($data['captcha_score']) ? (float) $data['captcha_score'] : null,
                'site_key' => $this->nullable($data['captcha_site_key'] ?? null),
                'secret_key' => $this->nullable($data['captcha_secret_key'] ?? null),
            ], fn ($v) => $v !== null),
            'third_party_firebase' => array_filter([
                'enabled' => isset($data['firebase_enabled']) ? (bool) $data['firebase_enabled'] : null,
                'credentials_path' => $this->nullable($data['firebase_credentials'] ?? null),
                'project_id' => $this->nullable($data['firebase_project'] ?? null),
                'api_key' => $this->nullable($data['firebase_api_key'] ?? null),
                'auth_domain' => $this->nullable($data['firebase_auth_domain'] ?? null),
                'database_url' => $this->nullable($data['firebase_database_url'] ?? null),
                'storage_bucket' => $this->nullable($data['firebase_storage_bucket'] ?? null),
                'messaging_sender_id' => $this->nullable($data['firebase_messaging_sender_id'] ?? null),
                'app_id' => $this->nullable($data['firebase_app_id'] ?? null),
                'measurement_id' => $this->nullable($data['firebase_measurement_id'] ?? null),
                'token_uri' => $this->nullable($data['firebase_token_uri'] ?? null),
            ], fn ($v) => $v !== null),
            'third_party_google_maps' => array_filter([
                'enabled' => isset($data['google_maps_enabled']) ? (bool) $data['google_maps_enabled'] : null,
                'client_key' => $this->nullable($data['google_maps_client_key'] ?? null),
                'server_key' => $this->nullable($data['google_maps_server_key'] ?? null),
            ], fn ($v) => $v !== null),
            'third_party_ai' => array_filter([
                'enabled' => isset($data['ai_enabled']) ? (bool) $data['ai_enabled'] : null,
                'provider' => $this->nullable($data['ai_provider'] ?? null),
            ], fn ($v) => $v !== null),
            'third_party_openai' => array_filter([
                'api_key' => $this->nullable($data['openai_api_key'] ?? null),
                'base_url' => $this->nullable($data['openai_base_url'] ?? null),
                'model' => $this->nullable($data['openai_model'] ?? null),
                'temperature' => isset($data['openai_temperature']) ? (float) $data['openai_temperature'] : null,
                'max_tokens' => $this->nullableInt($data['openai_max_tokens'] ?? null),
                'request_timeout' => isset($data['openai_request_timeout']) ? (int) $data['openai_request_timeout'] : null,
                'writing_style' => $this->nullable($data['openai_writing_style'] ?? null),
                'news_prompt' => $this->nullable($data['openai_news_prompt'] ?? null),
                'article_prompt' => $this->nullable($data['openai_article_prompt'] ?? null),
                'default_prompt' => $this->nullable($data['openai_default_prompt'] ?? null),
                'ai_rewrite_prompt' => $this->nullable($data['openai_ai_rewrite_prompt'] ?? null),
                'ai_seo_prompt' => $this->nullable($data['openai_ai_seo_prompt'] ?? null),
                'ai_tags_prompt' => $this->nullable($data['openai_ai_tags_prompt'] ?? null),
            ], fn ($v) => $v !== null),
            'third_party_gemini' => array_filter([
                'api_key' => $this->nullable($data['gemini_api_key'] ?? null),
                'model' => $this->nullable($data['gemini_model'] ?? null),
                'temperature' => isset($data['gemini_temperature']) ? (float) $data['gemini_temperature'] : null,
                'max_tokens' => $this->nullableInt($data['gemini_max_tokens'] ?? null),
                'request_timeout' => isset($data['gemini_request_timeout']) ? (int) $data['gemini_request_timeout'] : null,
                'prompt' => $this->nullable($data['gemini_prompt'] ?? null),
            ], fn ($v) => $v !== null),
            'third_party_whatsapp' => array_filter([
                'enabled' => isset($data['whatsapp_enabled']) ? (bool) $data['whatsapp_enabled'] : null,
                'instance_id' => $this->nullable($data['whatsapp_instance_id'] ?? null),
                'token' => $this->nullable($data['whatsapp_token'] ?? null),
                'base_url' => $this->nullable($data['whatsapp_base_url'] ?? null),
                'batch_size' => isset($data['whatsapp_batch_size']) ? (int) $data['whatsapp_batch_size'] : null,
                'sleep' => isset($data['whatsapp_sleep']) ? (int) $data['whatsapp_sleep'] : null,
            ], fn ($v) => $v !== null),
            'third_party_app_links' => array_filter([
                'google_play_url' => $this->nullable($data['google_play_url'] ?? null),
                'apple_store_url' => $this->nullable($data['apple_store_url'] ?? null),
            ], fn ($v) => $v !== null),
        ];

        foreach ($groups as $group => $values) {
            if (! empty($values)) {
                $this->setValues($group, $values);
            }
        }

        // Update runtime config
        config()->set('captcha.enabled', (bool) ($data['captcha_enabled'] ?? config('captcha.enabled')));
        config()->set('captcha.score', (float) ($data['captcha_score'] ?? config('captcha.score')));
        config()->set('captcha.v3_sitekey', (string) ($data['captcha_site_key'] ?? config('captcha.v3_sitekey')));
        config()->set('captcha.v3_secret', (string) ($data['captcha_secret_key'] ?? config('captcha.v3_secret')));
        config()->set('services.google.client_id', $this->nullable($data['google_client_id'] ?? config('services.google.client_id')));
        config()->set('services.google.client_secret', $this->nullable($data['google_client_secret'] ?? config('services.google.client_secret')));
        config()->set('services.google.redirect', $this->nullable($data['google_redirect_url'] ?? config('services.google.redirect')));
        config()->set('services.facebook.client_id', $this->nullable($data['facebook_client_id'] ?? config('services.facebook.client_id')));
        config()->set('services.facebook.client_secret', $this->nullable($data['facebook_client_secret'] ?? config('services.facebook.client_secret')));
        config()->set('services.facebook.redirect', $this->nullable($data['facebook_redirect_url'] ?? config('services.facebook.redirect')));
    }

    public function setFirebaseServiceAccountPath(string $relativePathFromProjectRoot, ?string $projectId = null): void
    {
        $rows = ['credentials_path' => $relativePathFromProjectRoot];
        if ($projectId !== null && $projectId !== '') {
            $rows['project_id'] = $projectId;
        }
        $this->setValues('third_party_firebase', $rows);

        $abs = $this->absolutePathFromProjectRelative($relativePathFromProjectRoot);
        if ($abs !== null && is_file($abs)) {
            config()->set('firebase.projects.app.credentials', $abs);
        }
    }

    public function applyFirebaseRuntimeConfigFromDatabase(): void
    {
        $tp = $this->getThirdParty();
        $rel = trim((string) ($tp['firebase_credentials'] ?? ''));
        if ($rel === '') {
            return;
        }
        $abs = $this->absolutePathFromProjectRelative($rel);
        if ($abs === null || ! is_file($abs)) {
            return;
        }
        config()->set('firebase.projects.app.credentials', $abs);
    }

    public function applySocialAuthRuntimeConfigFromDatabase(): void
    {
        $tp = $this->getThirdParty();

        $gId = trim((string) $tp['google_client_id']);
        if ($gId !== '') {
            config()->set('services.google.client_id', $gId);
        }
        $gSec = trim((string) $tp['google_client_secret']);
        if ($gSec !== '') {
            config()->set('services.google.client_secret', $gSec);
        }
        $gRed = trim((string) $tp['google_redirect_url']);
        config()->set('services.google.redirect', $gRed !== '' ? $gRed : $this->defaultOAuthRedirectForProvider('google'));

        $fId = trim((string) $tp['facebook_client_id']);
        if ($fId !== '') {
            config()->set('services.facebook.client_id', $fId);
        }
        $fSec = trim((string) $tp['facebook_client_secret']);
        if ($fSec !== '') {
            config()->set('services.facebook.client_secret', $fSec);
        }
        $fRed = trim((string) $tp['facebook_redirect_url']);
        config()->set('services.facebook.redirect', $fRed !== '' ? $fRed : $this->defaultOAuthRedirectForProvider('facebook'));
    }

    private function defaultOAuthRedirectForProvider(string $provider): string
    {
        if (! in_array($provider, ['google', 'facebook'], true)) {
            $provider = 'google';
        }

        $path = '/auth/'.$provider.'/callback';

        // Finding #10: Use app.url instead of request host to prevent Host header poisoning.
        $base = rtrim((string) config('app.url'), '/');

        return $base.$path;
    }

    private function absolutePathFromProjectRelative(string $path): ?string
    {
        $path = trim($path);
        if ($path === '') {
            return null;
        }
        if (str_starts_with($path, '/') || str_starts_with($path, '\\') || (strlen($path) > 2 && ctype_alpha($path[0]) && $path[1] === ':')) {
            return $path;
        }

        return base_path($path);
    }

    public function resolveAI(): array
    {
        $data = $this->getThirdParty();
        $enabled = (bool) ($data['ai_enabled'] ?? false);
        $provider = $data['ai_provider'] ?? 'openai';

        $config = match ($provider) {
            'gemini' => [
                'provider' => 'gemini',
                'api_key' => (string) ($data['gemini_api_key'] ?? ''),
                'model' => (string) ($data['gemini_model'] ?? 'gemini-2.5-flash'),
                'base_url' => null,
                'temperature' => (float) ($data['gemini_temperature'] ?? 0.4),
                'max_tokens' => isset($data['gemini_max_tokens']) ? (int) $data['gemini_max_tokens'] : null,
                'timeout' => (int) ($data['gemini_request_timeout'] ?? 30),
                'prompt' => $data['gemini_prompt'] ?? null,
                'prompts' => [
                    'rewrite' => $data['gemini_prompt'] ?? null,
                    'seo' => $data['gemini_prompt'] ?? null,
                    'tags' => $data['gemini_prompt'] ?? null,
                    'news' => $data['gemini_prompt'] ?? null,
                    'article' => $data['gemini_prompt'] ?? null,
                ],
            ],
            default => [
                'provider' => 'openai',
                'api_key' => (string) ($data['openai_api_key'] ?? ''),
                'model' => (string) ($data['openai_model'] ?? 'gpt-4o-mini'),
                'base_url' => (string) ($data['openai_base_url'] ?? 'https://api.openai.com/v1'),
                'temperature' => (float) ($data['openai_temperature'] ?? 0.4),
                'max_tokens' => isset($data['openai_max_tokens']) ? (int) $data['openai_max_tokens'] : null,
                'timeout' => (int) ($data['openai_request_timeout'] ?? 30),
                'prompt' => $data['openai_default_prompt'] ?? null,
                'prompts' => [
                    'rewrite' => $data['openai_ai_rewrite_prompt'] ?? null,
                    'seo' => $data['openai_ai_seo_prompt'] ?? null,
                    'tags' => $data['openai_ai_tags_prompt'] ?? null,
                    'news' => $data['openai_news_prompt'] ?? null,
                    'article' => $data['openai_article_prompt'] ?? null,
                ],
            ],
        };

        return array_merge(['enabled' => $enabled], $config);
    }

    // ─── Media (General Assets) ───────────────────────────────────────

    public function getGeneralMediaUrls(): array
    {
        $asset = $this->getOrCreateAsset('general');

        return [
            'logo_light_url' => $asset->logo_light_url,
            'logo_dark_url' => $asset->logo_dark_url,
            'favicon_url' => $asset->favicon_url,
            'watermark_image_url' => $asset->watermark_image_url,
        ];
    }

    public function uploadGeneralMedia(string $collection, UploadedFile $file): void
    {
        $allowed = ['logo_light', 'logo_dark', 'favicon', 'watermark_image'];

        if (! in_array($collection, $allowed, true)) {
            return;
        }

        $validator = Validator::make(
            ['file' => $file],
            ['file' => ['required', 'file', 'mimes:png,jpg,jpeg,svg,webp,ico', 'max:2048']],
        );

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }

        $asset = $this->getOrCreateAsset('general');
        $safeName = Str::slug(pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME))
            .'-'.uniqid()
            .'.'.$file->getClientOriginalExtension();

        $asset->addMedia($file)
            ->usingFileName($safeName)
            ->toMediaCollection($collection);
    }

    public function clearGeneralMedia(string $collection): void
    {
        $allowed = ['logo_light', 'logo_dark', 'favicon', 'watermark_image'];

        if (! in_array($collection, $allowed, true)) {
            return;
        }

        $asset = $this->getOrCreateAsset('general');
        $asset->clearMediaCollection($collection);
    }

    private function getOrCreateAsset(string $group): SettingAsset
    {
        return SettingAsset::firstOrCreate(['group' => $group]);
    }

    // ─── CDN (Spatie Settings) ─────────────────────────────────────────

    public function getCDN(): array
    {
        $settings = app(CDNSettings::class);

        return [
            'cdn_enabled' => $settings->cdn_enabled,
            'cdn_provider' => $settings->cdn_provider,
            'cdn_token' => $settings->cdn_token,
            'cdn_zone_id' => $settings->cdn_zone_id,
            'cdn_plan' => $settings->cdn_plan,
            'cdn_auto_purge' => $settings->cdn_auto_purge,
            'cdn_fallback_provider' => $settings->cdn_fallback_provider,
            'cdn_fallback_token' => $settings->cdn_fallback_token,
            'cdn_fallback_zone_id' => $settings->cdn_fallback_zone_id,
            'cdn_fallback_plan' => $settings->cdn_fallback_plan,
            'cdn_alert_email' => $settings->cdn_alert_email,
            'cdn_alert_slack_webhook' => $settings->cdn_alert_slack_webhook,
        ];
    }

    public function getCDNSafeForFrontend(): array
    {
        $data = $this->getCDN();

        $data['has_cdn_token'] = ! empty($data['cdn_token']);
        $data['has_fallback_token'] = ! empty($data['cdn_fallback_token']);

        // Mask secrets
        $data['cdn_token'] = '';
        $data['cdn_fallback_token'] = '';

        return $data;
    }

    public function getCDNDecrypted(): array
    {
        $data = $this->getCDN();

        if ($data['cdn_token'] !== '') {
            try {
                $data['cdn_token'] = Crypt::decryptString($data['cdn_token']);
            } catch (\Throwable) {
                // plaintext
            }
        }

        if (($data['cdn_fallback_token'] ?? '') !== '') {
            try {
                $data['cdn_fallback_token'] = Crypt::decryptString($data['cdn_fallback_token']);
            } catch (\Throwable) {
                // plaintext
            }
        }

        return $data;
    }

    public function saveCDN(array $data): void
    {
        $settings = app(CDNSettings::class);

        $settings->cdn_enabled = (bool) ($data['cdn_enabled'] ?? false);
        $settings->cdn_provider = (string) ($data['cdn_provider'] ?? 'cloudflare');

        // Finding #12: Only update secret if a new value is provided.
        $token = (string) ($data['cdn_token'] ?? '');
        if ($token !== '') {
            $settings->cdn_token = Crypt::encryptString($token);
        }

        $settings->cdn_zone_id = (string) ($data['cdn_zone_id'] ?? '');
        $settings->cdn_plan = (string) ($data['cdn_plan'] ?? 'free');
        $settings->cdn_auto_purge = (bool) ($data['cdn_auto_purge'] ?? false);

        $settings->cdn_fallback_provider = (string) ($data['cdn_fallback_provider'] ?? '');

        $fallbackToken = (string) ($data['cdn_fallback_token'] ?? '');
        if ($fallbackToken !== '') {
            $settings->cdn_fallback_token = Crypt::encryptString($fallbackToken);
        }

        $settings->cdn_fallback_zone_id = (string) ($data['cdn_fallback_zone_id'] ?? '');
        $settings->cdn_fallback_plan = (string) ($data['cdn_fallback_plan'] ?? 'free');

        $settings->cdn_alert_email = (string) ($data['cdn_alert_email'] ?? '');
        $settings->cdn_alert_slack_webhook = (string) ($data['cdn_alert_slack_webhook'] ?? '');

        $settings->save();
    }
}
