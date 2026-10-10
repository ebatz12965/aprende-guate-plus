<?php

namespace App\Providers;

use App\Models\PlatformSetting;
use App\Models\User;
use App\Support\AdminAccess;
use App\Support\Modules;
use Illuminate\Auth\Events\Login;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Laravel\Telescope\TelescopeServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Un registro por petición: el estado de los módulos se lee una vez aunque el menú lo consulte muchas veces.
        $this->app->scoped(Modules::class);

        if ($this->app->environment('local')
            && config('telescope.enabled')
            && class_exists(TelescopeServiceProvider::class)) {
            $this->app->register(\App\Providers\TelescopeServiceProvider::class);
        }
    }

    public function boot(): void
    {
        // El producto es solo en español: las fechas ("hace 2 horas", "miércoles") no dependen de APP_LOCALE.
        Carbon::setLocale('es');

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

        // El super administrador recibe solo las habilidades del catálogo de administración. No hay un
        // Gate::before general: las policies protegen datos personales (diagnósticos, evidencias,
        // conversaciones) y no deben omitirse por rol.
        Gate::before(fn (User $user, string $ability) => AdminAccess::isAdminAbility($ability) && $user->isSuperAdmin() ? true : null);
        foreach (array_keys(AdminAccess::SUPER_ONLY) as $ability) {
            Gate::define($ability, fn () => false);
        }
        Gate::define('roles.ver', fn (User $user) => $user->can('roles.gestionar') || $user->can('roles.asignar'));

        // Al iniciar sesión (contraseña, Google, 2FA o «recordarme») se reabre el último espacio, si sigue permitido,
        // y el contador de inactividad empieza de cero.
        Event::listen(Login::class, function (Login $event) {
            if (! $event->user instanceof User) {
                return;
            }
            $user = $event->user;
            $allowed = match ($user->last_space) {
                'admin' => $user->canAccessAdmin(),
                'mentor' => $user->hasRole('Instructor') || $user->isSuperAdmin(),
                'learning' => true,
                default => false,
            };
            if ($allowed) {
                session()->put('active_space', $user->last_space);
            }
            session()->put('idle.last_activity', now()->timestamp);
        });

        // @module('tutor') … @endmodule: muestra el enlace si el módulo no está oculto en vista previa para esta persona.
        Blade::if('module', fn (string $key) => app(Modules::class)->visibleTo(auth()->user(), $key));
    }
}
