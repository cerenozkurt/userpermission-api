<?php

namespace App\Providers;

use App\Policies\PostPolicy;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Gate;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The policy mappings for the application.
     *
     * @var array<class-string, class-string>
     */
    protected $policies = [
        // 'App\Models\Model' => 'App\Policies\ModelPolicy',
        
    ];

    /**
     * Register any authentication / authorization services.
     *
     * @return void
     */
    public function boot()
    {
        $this->registerPolicies();

        //superadmin tüm izinlere sahiptir
       /*  Gate::before(function ($user, $ability) {
           return $user->hasRole('superadmin') ? true : null;
        });*/

        Gate::define('post-create',[PostPolicy::class, 'create']);
        Gate::define('post-update',[PostPolicy::class, 'update']);
        Gate::define('post-delete',[PostPolicy::class, 'delete']);
        Gate::define('post-ownpostcontrol',[PostPolicy::class, 'ownPostControl']);
        Gate::define('post-gradualpermission', [PostPolicy::class, 'gradualPermission']);
    }
}
