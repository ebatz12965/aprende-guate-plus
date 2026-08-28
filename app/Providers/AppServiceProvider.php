<?php

namespace App\Providers;

use App\Models\ClassModel;
use App\Models\Course;
use App\Models\Module;
use App\Models\User;
use App\Policies\ClassPolicy;
use App\Policies\CoursePolicy;
use App\Policies\ModulePolicy;
use App\Policies\UserPolicy;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;

class AppServiceProvider extends ServiceProvider
{
    protected $policies = [
        User::class => UserPolicy::class,
        Course::class => CoursePolicy::class,
        Module::class => ModulePolicy::class,
        ClassModel::class => ClassPolicy::class,
    ];

    public function boot(): void
    {
        $this->registerPolicies();

        RateLimiter::for('ai', function (Request $request) {
            $identity = $request->user()?->id ?? $request->ip();

            return [
                Limit::perMinute(6)->by('ai-minute:'.$identity),
                Limit::perDay(60)->by('ai-day:'.$identity),
            ];
        });

        Gate::before(function ($user, $ability) {
            return $user->hasRole('Admin') ? true : null;
        });

        Gate::define('manage-users', fn ($user) => $user->hasRole(['Admin']));
        Gate::define('manage-courses', fn ($user) => $user->hasRole(['Admin', 'Instructor']));
        Gate::define('view-analytics', fn ($user) => $user->hasRole(['Admin', 'Instructor']));
    }
}
