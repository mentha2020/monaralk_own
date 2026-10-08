<?php

use App\Models\User;
use App\Modules\Accounts\Policies\UserPolicy;
use App\Modules\Catalog\Models\Vehicle;
use App\Modules\Catalog\Policies\VehiclePolicy;
use App\Modules\Leads\Models\Enquiry;
use App\Modules\Leads\Policies\EnquiryPolicy;
use App\Modules\Submissions\Models\VehicleSubmission;
use App\Modules\Submissions\Policies\SubmissionPolicy;
use Database\Factories\EnquiryFactory;
use Database\Factories\VehicleFactory;
use Database\Factories\VehicleSubmissionFactory;
use Database\Seeders\RoleSeeder;
use Illuminate\Support\Facades\Gate;

beforeEach(function () {
    $this->seed(RoleSeeder::class);
});

test('every module policy is registered on the gate', function () {
    $registered = [
        Vehicle::class => VehiclePolicy::class,
        Enquiry::class => EnquiryPolicy::class,
        VehicleSubmission::class => SubmissionPolicy::class,
        User::class => UserPolicy::class,
    ];

    foreach ($registered as $model => $policy) {
        expect(get_class(Gate::getPolicyFor($model)))->toBe($policy);
    }
});

test('models resolve flat factory classes through the discovery hook', function () {
    expect(get_class(Vehicle::factory()))->toBe(VehicleFactory::class)
        ->and(get_class(Enquiry::factory()))->toBe(EnquiryFactory::class)
        ->and(get_class(VehicleSubmission::factory()))->toBe(VehicleSubmissionFactory::class);
});

test('viewer can read but not modify vehicles', function () {
    $user = staff('viewer');
    $vehicle = Vehicle::factory()->create();

    expect($user->can('viewAny', Vehicle::class))->toBeTrue()
        ->and($user->can('view', $vehicle))->toBeTrue()
        ->and($user->can('create', Vehicle::class))->toBeFalse()
        ->and($user->can('update', $vehicle))->toBeFalse()
        ->and($user->can('delete', $vehicle))->toBeFalse()
        ->and($user->can('publish', $vehicle))->toBeFalse();
});

test('editor can create and update but not delete or publish', function () {
    $user = staff('editor');
    $vehicle = Vehicle::factory()->create();

    expect($user->can('create', Vehicle::class))->toBeTrue()
        ->and($user->can('update', $vehicle))->toBeTrue()
        ->and($user->can('delete', $vehicle))->toBeFalse()
        ->and($user->can('publish', $vehicle))->toBeFalse();
});

test('admin can manage and publish vehicles', function () {
    $user = staff('admin');
    $vehicle = Vehicle::factory()->create();

    expect($user->can('delete', $vehicle))->toBeTrue()
        ->and($user->can('publish', $vehicle))->toBeTrue()
        ->and($user->hasRole('admin'))->toBeTrue()
        ->and($user->can('role.manage'))->toBeFalse();
});

test('super admin holds every vehicle ability', function () {
    $user = staff('super_admin');
    $vehicle = Vehicle::factory()->create();

    expect($user->can('viewAny', Vehicle::class))->toBeTrue()
        ->and($user->can('create', Vehicle::class))->toBeTrue()
        ->and($user->can('update', $vehicle))->toBeTrue()
        ->and($user->can('delete', $vehicle))->toBeTrue()
        ->and($user->can('publish', $vehicle))->toBeTrue();
});

test('a user with no roles is denied vehicle access', function () {
    $user = User::factory()->create();
    $vehicle = Vehicle::factory()->create();

    expect($user->can('viewAny', Vehicle::class))->toBeFalse()
        ->and($user->can('update', $vehicle))->toBeFalse();
});

test('any authenticated user may create an enquiry or a submission', function () {
    $user = User::factory()->create();

    expect($user->can('create', Enquiry::class))->toBeTrue()
        ->and($user->can('create', VehicleSubmission::class))->toBeTrue()
        ->and($user->can('viewAny', Enquiry::class))->toBeFalse()
        ->and($user->can('review', VehicleSubmission::factory()->create()))->toBeFalse();
});

test('enquiry and submission pipelines require the matching permission', function () {
    $viewer = staff('viewer');
    $editor = staff('editor');
    $enquiry = Enquiry::factory()->create();
    $submission = VehicleSubmission::factory()->create();

    expect($viewer->can('viewAny', Enquiry::class))->toBeTrue()
        ->and($viewer->can('update', $enquiry))->toBeFalse()
        ->and($viewer->can('review', $submission))->toBeFalse()
        ->and($editor->can('update', $enquiry))->toBeTrue()
        ->and($editor->can('viewAny', Enquiry::class))->toBeTrue()
        ->and($editor->can('review', $submission))->toBeFalse();
});

test('super admin can review submissions', function () {
    $user = staff('super_admin');
    $submission = VehicleSubmission::factory()->create();

    expect($user->can('review', $submission))->toBeTrue()
        ->and($user->can('update', $submission))->toBeTrue();
});

test('user policy guards profile access and self deletion', function () {
    $admin = staff('admin');
    $plain = User::factory()->create();

    expect($admin->can('viewAny', User::class))->toBeTrue()
        ->and($admin->can('update', $plain))->toBeTrue()
        ->and($admin->can('delete', $plain))->toBeTrue()
        ->and($admin->can('delete', $admin))->toBeFalse();

    expect($plain->can('viewAny', User::class))->toBeFalse()
        ->and($plain->can('update', $plain))->toBeFalse()
        ->and($plain->can('view', $plain))->toBeTrue();
});
