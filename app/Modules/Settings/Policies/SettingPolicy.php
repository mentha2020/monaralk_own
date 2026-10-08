<?php

namespace App\Modules\Settings\Policies;

use App\Models\User;

class SettingPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('setting.manage');
    }

    public function view(User $user): bool
    {
        return $user->can('setting.manage');
    }

    public function create(User $user): bool
    {
        return $user->can('setting.manage');
    }

    public function update(User $user): bool
    {
        return $user->can('setting.manage');
    }

    public function delete(User $user): bool
    {
        return $user->can('setting.manage');
    }
}
