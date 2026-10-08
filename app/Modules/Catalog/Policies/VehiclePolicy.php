<?php

namespace App\Modules\Catalog\Policies;

use App\Models\User;

class VehiclePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('vehicle.view');
    }

    public function view(User $user, $vehicle): bool
    {
        return $user->can('vehicle.view');
    }

    public function create(User $user): bool
    {
        return $user->can('vehicle.create');
    }

    public function update(User $user, $vehicle): bool
    {
        return $user->can('vehicle.update');
    }

    public function delete(User $user, $vehicle): bool
    {
        return $user->can('vehicle.delete');
    }

    public function publish(User $user, $vehicle): bool
    {
        return $user->can('vehicle.publish');
    }

    public function restore(User $user, $vehicle): bool
    {
        return $user->can('vehicle.delete');
    }

    public function forceDelete(User $user, $vehicle): bool
    {
        return $user->can('vehicle.delete');
    }
}
