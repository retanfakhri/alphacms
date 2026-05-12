<?php

namespace App\Providers;

use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use App\Http\Responses\LoginResponse;
use App\Services\CDN\CDNCircuitBreaker;
use App\Services\CDN\CDNManager;
use App\Services\CDN\CDNPurgeBuffer;
use App\Settings\CDNSettings;
use App\Support\CDN\WarmupThrottle;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rules\Password;
use Laravel\Fortify\Contracts\LoginResponse as LoginResponseContract;
use App\Auth\AdminEligibleUserProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(LoginResponseContract::class, LoginResponse::class);

        // CDN Subsystem
        $this->app->singleton(CDNManager::class);
        $this->app->singleton(CDNPurgeBuffer::class);
        $this->app->singleton(CDNCircuitBreaker::class);
        $this->app->singleton(WarmupThrottle::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Auth::provider('admin_eligible', function ($app, array $config) {
            return new AdminEligibleUserProvider(
                $app['hash'],
                $config['model'] ?? \App\Models\User::class,
            );
        });

        \App\Models\User::observe(\App\Observers\UserObserver::class);
        $this->configureDefaults();

        if (app()->isProduction() && ! \Illuminate\Support\Facades\Cache::supportsTags()) {
            throw new \RuntimeException('The CDN subsystem requires a cache driver that supports tags (Redis or Memcached) in production.');
        }
    }

    /**
     * Configure default behaviors for production-ready applications.
     */
    protected function configureDefaults(): void
    {
        Date::use(CarbonImmutable::class);

        DB::prohibitDestructiveCommands(
            app()->isProduction(),
        );

        Password::defaults(fn (): ?Password => app()->isProduction()
            ? Password::min(12)
                ->mixedCase()
                ->letters()
                ->numbers()
                ->symbols()
                ->uncompromised()
            : null,
        );
    }
}
