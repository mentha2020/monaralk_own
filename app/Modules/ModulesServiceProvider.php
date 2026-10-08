<?php

namespace App\Modules;

use App\Models\User;
use App\Modules\Accounts\Policies\UserPolicy;
use App\Modules\Catalog\Models\Vehicle;
use App\Modules\Catalog\Policies\VehiclePolicy;
use App\Modules\Leads\Models\Enquiry;
use App\Modules\Leads\Policies\EnquiryPolicy;
use App\Modules\Submissions\Models\VehicleSubmission;
use App\Modules\Submissions\Policies\SubmissionPolicy;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

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

        Gate::policy(Vehicle::class, VehiclePolicy::class);
        Gate::policy(Enquiry::class, EnquiryPolicy::class);
        Gate::policy(VehicleSubmission::class, SubmissionPolicy::class);
        Gate::policy(User::class, UserPolicy::class);
    }
}
