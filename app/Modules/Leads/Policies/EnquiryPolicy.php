<?php

namespace App\Modules\Leads\Policies;

use App\Models\User;
use App\Modules\Leads\Models\Enquiry;

class EnquiryPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('enquiry.view');
    }

    public function view(User $user, Enquiry $enquiry): bool
    {
        return $user->can('enquiry.view');
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, Enquiry $enquiry): bool
    {
        return $user->can('enquiry.update');
    }

    public function delete(User $user, Enquiry $enquiry): bool
    {
        return $user->can('enquiry.update');
    }
}
