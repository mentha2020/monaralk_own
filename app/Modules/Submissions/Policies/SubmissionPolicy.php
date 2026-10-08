<?php

namespace App\Modules\Submissions\Policies;

use App\Models\User;
use App\Modules\Submissions\Models\VehicleSubmission;

class SubmissionPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('submission.view');
    }

    public function view(User $user, VehicleSubmission $submission): bool
    {
        return $user->can('submission.view');
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function review(User $user, VehicleSubmission $submission): bool
    {
        return $user->can('submission.review');
    }

    public function update(User $user, VehicleSubmission $submission): bool
    {
        return $user->can('submission.review');
    }

    public function delete(User $user, VehicleSubmission $submission): bool
    {
        return $user->can('submission.review');
    }
}
