<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\SettingsManagerService;
use App\Services\CDN\CDNManager;
use App\Services\CDN\CDNStats;
use App\Services\CDN\CDNCircuitBreaker;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Illuminate\Http\RedirectResponse;

class SettingController extends Controller
{
    public function __construct(
        protected SettingsManagerService $settings,
        protected CDNManager $cdnManager
    ) {}

    /**
     * Display General Settings.
     */
    public function general(): Response
    {
        return Inertia::render('admin/settings/index', [
            'settings' => $this->settings->getGeneral(),
            'media' => $this->settings->getGeneralMediaUrls(),
            'smtp' => $this->settings->getSmtpSafeForFrontend(),
            'social' => $this->settings->getSocial(),
            'tracking' => $this->settings->getTracking(),
            'timezones' => $this->getTimezones(),
        ]);
    }

    /**
     * Get list of timezones with offsets.
     */
    private function getTimezones(): array
    {
        $timezones = [];
        foreach (\DateTimeZone::listIdentifiers() as $timezone) {
            $dateTime = new \DateTime('now', new \DateTimeZone($timezone));
            $offset = $dateTime->getOffset();
            $hours = floor(abs($offset) / 3600);
            $minutes = floor((abs($offset) % 3600) / 60);
            $sign = $offset >= 0 ? '+' : '-';
            
            $formattedOffset = "GMT{$sign}" . str_pad((string)$hours, 2, '0', STR_PAD_LEFT) . ":" . str_pad((string)$minutes, 2, '0', STR_PAD_LEFT);
            
            $timezones[] = [
                'id' => $timezone,
                'label' => "( $formattedOffset ) " . str_replace('_', ' ', $timezone),
            ];
        }

        return $timezones;
    }

    /**
     * Update General Settings.
     */
    public function updateGeneral(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'site_name' => ['required', 'string', 'max:255'],
            'site_url' => ['required', 'url', 'max:255'],
            'timezone' => ['required', 'string', 'max:100'],
            'site_email' => ['nullable', 'email', 'max:255'],
            'site_phone' => ['nullable', 'string', 'max:50'],
            'copyright_text' => ['nullable', 'string', 'max:500'],
            'footer_attribution' => ['nullable', 'string', 'max:500'],
            'cookies_text' => ['nullable', 'string', 'max:1000'],
            'enable_watermark' => ['boolean'],
            'enable_comments' => ['boolean'],
        ]);

        $this->settings->saveGeneral($validated);

        return back()->with('toast', [
            'type' => 'success',
            'message' => __('General settings updated successfully.')
        ]);
    }

    /**
     * Update SMTP Settings.
     */
    public function updateSmtp(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'mailer' => ['required', 'string', 'in:smtp,log'],
            'host' => ['required_if:mailer,smtp', 'nullable', 'string'],
            'port' => ['required_if:mailer,smtp', 'nullable', 'integer'],
            'encryption' => ['nullable', 'string', 'in:tls,ssl'],
            'username' => ['nullable', 'string'],
            'password' => ['nullable', 'string'],
            'from_email' => ['required', 'email'],
            'from_name' => ['required', 'string'],
        ]);

        $this->settings->saveSmtp($validated);

        return back()->with('toast', [
            'type' => 'success',
            'message' => __('SMTP settings updated successfully.')
        ]);
    }

    /**
     * Update Social Settings.
     */
    public function updateSocial(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'facebook' => ['nullable', 'url'],
            'twitter' => ['nullable', 'url'],
            'instagram' => ['nullable', 'url'],
            'linkedin' => ['nullable', 'url'],
            'youtube' => ['nullable', 'url'],
            'tiktok' => ['nullable', 'url'],
            'whatsapp_number' => ['nullable', 'string'],
        ]);

        $this->settings->saveSocial($validated);

        return back()->with('toast', [
            'type' => 'success',
            'message' => __('Social settings updated successfully.')
        ]);
    }

    /**
     * Update Tracking Settings.
     */
    public function updateTracking(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'google_analytics' => ['nullable', 'string'],
            'facebook_pixel' => ['nullable', 'string'],
            'google_meta_tag' => ['nullable', 'string'],
        ]);

        $this->settings->saveTracking($validated);

        return back()->with('toast', [
            'type' => 'success',
            'message' => __('Tracking settings updated successfully.')
        ]);
    }

    /**
     * Display Third Party Settings.
     */
    public function thirdParty(): Response
    {
        return Inertia::render('admin/settings/third-party', [
            'settings' => $this->settings->getThirdPartySafeForFrontend(),
        ]);
    }

    /**
     * Update Third Party Settings.
     */
    public function updateThirdParty(Request $request): RedirectResponse
    {
        $this->settings->saveThirdParty($request->all());

        return back()->with('toast', [
            'type' => 'success',
            'message' => __('Third party settings updated successfully.')
        ]);
    }

    /**
     * Display CDN Settings.
     */
    public function cdn(): Response
    {
        $stats = null;
        $circuitStatus = null;

        $cdnSettings = $this->settings->getCDNSafeForFrontend();

        if ($cdnSettings['cdn_enabled']) {
            $stats = CDNStats::summary(24);
            $circuitStatus = app(CDNCircuitBreaker::class)->status();
        }

        return Inertia::render('admin/settings/cdn', [
            'settings' => $cdnSettings,
            'stats' => $stats,
            'circuitStatus' => $circuitStatus,
        ]);
    }

    /**
     * Update CDN Settings.
     */
    public function updateCdn(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'cdn_enabled' => ['boolean'],
            'cdn_provider' => ['required', 'string', 'in:cloudflare'],
            'cdn_token' => ['nullable', 'string'],
            'cdn_zone_id' => ['nullable', 'string'],
            'cdn_plan' => ['required', 'string'],
            'cdn_auto_purge' => ['boolean'],
        ]);

        $this->settings->saveCDN($validated);
        $this->cdnManager->refresh();

        return back()->with('toast', [
            'type' => 'success',
            'message' => __('CDN settings updated successfully.')
        ]);
    }

    /**
     * Upload site media (logo, favicon, etc).
     */
    public function uploadMedia(Request $request): RedirectResponse
    {
        $request->validate([
            'collection' => ['required', 'string', 'in:logo_light,logo_dark,favicon,watermark_image'],
            'file' => ['required', 'file', 'image', 'max:2048'],
        ]);

        $this->settings->uploadGeneralMedia($request->collection, $request->file('file'));

        return back()->with('toast', [
            'type' => 'success',
            'message' => __('Media uploaded successfully.')
        ]);
    }

    /**
     * Clear site media.
     */
    public function clearMedia(Request $request): RedirectResponse
    {
        $request->validate([
            'collection' => ['required', 'string', 'in:logo_light,logo_dark,favicon,watermark_image'],
        ]);

        $this->settings->clearGeneralMedia($request->collection);

        return back()->with('toast', [
            'type' => 'success',
            'message' => __('Media cleared successfully.')
        ]);
    }
}
