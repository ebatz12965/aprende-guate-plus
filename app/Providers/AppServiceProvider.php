<?php

namespace App\Providers;

use App\Models\PlatformSetting;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        if ($this->app->environment('local')
            && config('telescope.enabled')
            && class_exists(\Laravel\Telescope\TelescopeServiceProvider::class)) {
            $this->app->register(\App\Providers\TelescopeServiceProvider::class);
        }
    }

    public function boot(): void
    {
        $this->registerPolicies();

        // Conversación (tutor y guía) y generación de contenido tienen cuotas separadas
        // para que generar lecciones o retos no agote las consultas al tutor.
        RateLimiter::for('ai-tutor', function (Request $request) {
            $identity = $request->user()?->id ?? $request->ip();
            $settings = PlatformSetting::aiLimits();

            return [
                Limit::perMinute($settings['tutor_minute_limit'])->by('ai-tutor-minute:'.$identity),
                Limit::perDay($settings['tutor_daily_limit'])->by('ai-tutor-day:'.$identity),
            ];
        });

        RateLimiter::for('ai-generation', function (Request $request) {
            $identity = $request->user()?->id ?? $request->ip();
            $settings = PlatformSetting::aiLimits();

            return [
                Limit::perMinute($settings['generation_minute_limit'])->by('ai-generation-minute:'.$identity),
                Limit::perDay($settings['generation_daily_limit'])->by('ai-generation-day:'.$identity),
            ];
        });

        Gate::before(function ($user, $ability) {
            return $user->hasRole('Admin') ? true : null;
        });

        Gate::define('manage-users', fn ($user) => $user->hasRole(['Admin']));
        Gate::define('view-analytics', fn ($user) => $user->hasRole(['Admin', 'Instructor']));
    }
}
