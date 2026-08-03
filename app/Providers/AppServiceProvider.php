<?php

namespace App\Providers;
use App\Models\User;
use App\Models\Course;
use App\Models\Module;
use App\Models\ClassModel;
use App\Policies\UserPolicy;
use App\Policies\CoursePolicy;
use App\Policies\ModulePolicy;
use App\Policies\ClassPolicy;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Gate;

#use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * The model to policy mappings for the application.
     *
     * @var array<class-string, class-string>
     */
    protected $policies = [
        User::class => UserPolicy::class,
        Course::class => CoursePolicy::class,
        Module::class => ModulePolicy::class,
        ClassModel::class => ClassPolicy::class,
    ];

    /**
     * Register any authentication / authorization services.
     */
    public function boot(): void
    {
        $this->registerPolicies();

        // Gate para super admin
        Gate::before(function ($user, $ability) {
            return $user->hasRole('admin') ? true : null;
        });

        // Gates personalizados
        Gate::define('manage-users', function ($user) {
            return $user->hasRole(['admin']);
        });

        Gate::define('manage-courses', function ($user) {
            return $user->hasRole(['admin', 'instructor']);
        });

        Gate::define('view-analytics', function ($user) {
            return $user->hasRole(['admin', 'instructor']);
        });
    }
}
