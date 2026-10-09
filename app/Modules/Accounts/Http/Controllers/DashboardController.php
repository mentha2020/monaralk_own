<?php

namespace App\Modules\Accounts\Http\Controllers;

use App\Modules\Accounts\Services\SavedVehicles;
use App\Modules\Submissions\Enums\SubmissionStatus;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController
{
    public function __invoke(Request $request, SavedVehicles $saved): View
    {
        $user = $request->user();

        abort_unless($user !== null, 403);

        $counts = $saved->counts((int) $user->getKey());

        $submissions = $user->submissions()
            ->with('approvedVehicle')
            ->latest()
            ->limit(5)
            ->get();

        $pendingSubmissions = $user->submissions()
            ->where('status', SubmissionStatus::Pending)
            ->count();

        $listings = $user->vehicles()
            ->with(['make', 'model', 'coverImage'])
            ->latest()
            ->limit(5)
            ->get();

        return view('dashboard', [
            'user' => $user,
            'counts' => $counts,
            'submissions' => $submissions,
            'pendingSubmissions' => $pendingSubmissions,
            'listings' => $listings,
            'isStaff' => $user->isStaff(),
        ]);
    }
}
