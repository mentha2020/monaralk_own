<?php

use App\Modules\Catalog\Enums\VehicleSource;
use App\Modules\Catalog\Enums\VehicleStatus;
use App\Modules\Catalog\Models\Vehicle;
use App\Modules\Submissions\Enums\SubmissionStatus;
use App\Modules\Submissions\Models\VehicleSubmission;
use App\Modules\Submissions\Services\SubmissionApprover;
use Database\Seeders\RoleSeeder;
use Illuminate\Database\QueryException;

beforeEach(function () {
    $this->seed(RoleSeeder::class);
    $this->approver = app(SubmissionApprover::class);
});

test('approving a submission creates exactly one draft vehicle and links it back', function () {
    $admin = staff('admin');

    $submission = VehicleSubmission::factory()->create([
        'data' => [
            'make' => 'Toyota',
            'model' => 'Aqua',
            'year' => 2016,
            'mileage_km' => 62000,
            'price' => 6450000,
            'condition' => 'used',
            'location' => 'Colombo',
        ],
        'images' => ['vehicles/aqua-1.jpg', 'vehicles/aqua-2.jpg'],
    ]);

    $vehicle = $this->approver->approve($submission, $admin);

    expect(Vehicle::query()->count())->toBe(1)
        ->and($vehicle->source)->toBe(VehicleSource::Public)
        ->and($vehicle->status)->toBe(VehicleStatus::Draft)
        ->and($vehicle->year)->toBe(2016)
        ->and((float) $vehicle->price)->toBe(6450000.0)
        ->and($vehicle->views_count)->toBe(0)
        ->and($vehicle->make->name)->toBe('Toyota')
        ->and($vehicle->model->name)->toBe('Aqua')
        ->and($vehicle->images()->count())->toBe(2)
        ->and($vehicle->images()->where('is_cover', true)->count())->toBe(1)
        ->and($submission->refresh()->status)->toBe(SubmissionStatus::Approved)
        ->and($submission->approved_vehicle_id)->toBe($vehicle->getKey())
        ->and($submission->reviewed_by)->toBe($admin->getKey())
        ->and($submission->reviewed_at)->not->toBeNull();
});

test('a failure part way through approval rolls the whole thing back', function () {
    $submission = VehicleSubmission::factory()->create([
        'data' => [
            'make' => 'Toyota',
            'model' => 'Aqua',
            'year' => 2016,
            'price' => 6450000,
        ],
        'images' => [null],
    ]);

    expect(fn () => $this->approver->approve($submission, staff('admin')))
        ->toThrow(QueryException::class);

    expect(Vehicle::query()->count())->toBe(0)
        ->and($submission->refresh()->status)->toBe(SubmissionStatus::Pending)
        ->and($submission->approved_vehicle_id)->toBeNull()
        ->and($submission->reviewed_at)->toBeNull();
});

test('a submission without a price is refused before anything is written', function () {
    $submission = VehicleSubmission::factory()->create([
        'data' => ['make' => 'Toyota', 'model' => 'Aqua', 'year' => 2016],
    ]);

    expect(fn () => $this->approver->approve($submission, staff('admin')))
        ->toThrow(RuntimeException::class, 'Submission is missing the required [price] value.');

    expect(Vehicle::query()->count())->toBe(0)
        ->and($submission->refresh()->status)->toBe(SubmissionStatus::Pending);
});

test('rejecting a submission leaves the catalogue untouched', function () {
    $submission = VehicleSubmission::factory()->create();

    $this->approver->decide($submission, staff('admin'), SubmissionStatus::Rejected, 'Not roadworthy.');

    expect(Vehicle::query()->count())->toBe(0)
        ->and($submission->refresh()->status)->toBe(SubmissionStatus::Rejected)
        ->and($submission->notes)->toBe('Not roadworthy.')
        ->and($submission->reviewed_at)->not->toBeNull()
        ->and($submission->reviewed_by)->not->toBeNull();
});

test('an already approved submission cannot be approved twice', function () {
    $submission = VehicleSubmission::factory()->approved()->create();

    expect(fn () => $this->approver->approve($submission, staff('admin')))
        ->toThrow(RuntimeException::class, 'Submission has already been approved.');

    expect(Vehicle::query()->count())->toBe(0);
});
