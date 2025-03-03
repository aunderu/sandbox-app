<?php

namespace App\Providers;

use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Modules\Dashboard\Policies\InnovationPolicy;
use Modules\Dashboard\Policies\InnovationTypePolicy;
use Modules\Dashboard\Policies\SchoolPolicy;
use Modules\Dashboard\Policies\UserPolicy;
use Modules\Sandbox\Models\InnovationsModel;
use Modules\Sandbox\Models\InnovationTypesModel;
use Modules\Sandbox\Models\SchoolModel;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
       
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Gate::policy(SchoolModel::class, SchoolPolicy::class);
        Gate::policy(User::class, UserPolicy::class);
        Gate::policy(InnovationsModel::class, InnovationPolicy::class);
        Gate::policy(InnovationTypesModel::class, InnovationTypePolicy::class);
    }
}
