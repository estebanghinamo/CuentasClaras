<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->registerAuthRateLimiters();
        $this->registerWorkspaceRateLimiters();
    }

    private function registerAuthRateLimiters(): void
    {
        RateLimiter::for('auth-register', fn (Request $request) => Limit::perMinute(5)->by($request->ip()));

        RateLimiter::for(
            'auth-login',
            fn (Request $request) => Limit::perMinute(5)
                ->by(strtolower((string) $request->input('email')).'|'.$request->ip()),
        );

        RateLimiter::for(
            'auth-2fa',
            fn (Request $request) => Limit::perMinute(5)->by((string) ($request->user()->id ?? $request->ip())),
        );

        RateLimiter::for('auth-refresh', fn (Request $request) => Limit::perMinute(30)->by($request->ip()));

        RateLimiter::for(
            'auth-forgot',
            fn (Request $request) => [
                Limit::perMinute(3)->by('ip:'.$request->ip()),
                Limit::perMinute(3)->by('email:'.strtolower((string) $request->input('email'))),
            ],
        );

        RateLimiter::for(
            'auth-password',
            fn (Request $request) => Limit::perMinute(5)->by((string) ($request->user()->id ?? $request->ip())),
        );
    }

    private function registerWorkspaceRateLimiters(): void
    {
        RateLimiter::for(
            'invitations',
            fn (Request $request) => Limit::perMinute(10)->by((string) ($request->user()->id ?? $request->ip())),
        );

        RateLimiter::for('invitations-public', fn (Request $request) => Limit::perMinute(20)->by($request->ip()));
    }
}
