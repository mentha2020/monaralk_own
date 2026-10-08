<?php

namespace App\Modules\Settings\Policies;

use App\Models\User;

class PagePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('page.manage');
    }

    public function view(User $user): bool
    {
        return $user->can('page.manage');
    }

    public function create(User $user): bool
    {
        return $user->can('page.manage');
    }

    public function update(User $user): bool
    {
        return $user->can('page.manage');
    }

    public function delete(User $user): bool
    {
        return $user->can('page.manage');
    }
}
