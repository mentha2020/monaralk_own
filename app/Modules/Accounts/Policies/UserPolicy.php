<?php

namespace App\Modules\Accounts\Policies;

use App\Models\User;

class UserPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('user.view');
    }

    public function view(User $user, User $model): bool
    {
        return $user->can('user.view') || $user->is($model);
    }

    public function create(User $user): bool
    {
        return $user->can('user.manage');
    }

    public function update(User $user, User $model): bool
    {
        return $user->can('user.manage');
    }

    public function delete(User $user, User $model): bool
    {
        return $user->can('user.manage') && ! $user->is($model);
    }
}
