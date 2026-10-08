<?php

namespace App\Modules\Catalog\Policies;

use App\Models\User;

class VehicleTaxonomyPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('vehicle.view');
    }

    public function view(User $user): bool
    {
        return $user->can('vehicle.view');
    }

    public function create(User $user): bool
    {
        return $user->can('vehicle.create');
    }

    public function update(User $user): bool
    {
        return $user->can('vehicle.update');
    }

    public function delete(User $user): bool
    {
        return $user->can('vehicle.delete');
    }
}
