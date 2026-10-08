<?php

namespace App\Modules;

use App\Models\User;
use App\Modules\Accounts\Policies\RolePolicy;
use App\Modules\Accounts\Policies\UserPolicy;
use App\Modules\Catalog\Models\BodyType;
use App\Modules\Catalog\Models\Color;
use App\Modules\Catalog\Models\Feature;
use App\Modules\Catalog\Models\FuelType;
use App\Modules\Catalog\Models\Make;
use App\Modules\Catalog\Models\Transmission;
use App\Modules\Catalog\Models\Vehicle;
use App\Modules\Catalog\Models\VehicleImage;
use App\Modules\Catalog\Models\VehicleModel;
use App\Modules\Catalog\Observers\VehicleImageObserver;
use App\Modules\Catalog\Policies\VehiclePolicy;
use App\Modules\Catalog\Policies\VehicleTaxonomyPolicy;
use App\Modules\Leads\Models\Enquiry;
use App\Modules\Leads\Policies\EnquiryPolicy;
use App\Modules\Settings\Models\Page;
use App\Modules\Settings\Models\Setting;
use App\Modules\Settings\Policies\PagePolicy;
use App\Modules\Settings\Policies\SettingPolicy;
use App\Modules\Submissions\Models\VehicleSubmission;
use App\Modules\Submissions\Policies\SubmissionPolicy;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Spatie\Permission\Models\Role;

class ModulesServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        Factory::guessFactoryNamesUsing(
            fn (string $modelName) => 'Database\\Factories\\'.class_basename($modelName).'Factory'
        );

        $this->registerPolicies();
        $this->registerObservers();
    }

    private function registerPolicies(): void
    {
        Gate::policy(Vehicle::class, VehiclePolicy::class);
        Gate::policy(Enquiry::class, EnquiryPolicy::class);
        Gate::policy(VehicleSubmission::class, SubmissionPolicy::class);
        Gate::policy(User::class, UserPolicy::class);
        Gate::policy(Role::class, RolePolicy::class);
        Gate::policy(Page::class, PagePolicy::class);
        Gate::policy(Setting::class, SettingPolicy::class);

        foreach ([
            Make::class,
            VehicleModel::class,
            BodyType::class,
            FuelType::class,
            Transmission::class,
            Color::class,
            Feature::class,
        ] as $taxonomy) {
            Gate::policy($taxonomy, VehicleTaxonomyPolicy::class);
        }
    }

    private function registerObservers(): void
    {
        VehicleImage::observe(VehicleImageObserver::class);
    }
}
