<?php

namespace App\Modules\Accounts\Policies;

use App\Models\User;

class RolePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('role.manage');
    }

    public function view(User $user): bool
    {
        return $user->can('role.manage');
    }

    public function create(User $user): bool
    {
        return $user->can('role.manage');
    }

    public function update(User $user): bool
    {
        return $user->can('role.manage');
    }

    public function delete(User $user): bool
    {
        return $user->can('role.manage');
    }
}
