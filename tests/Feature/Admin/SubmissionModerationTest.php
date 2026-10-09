<?php

use App\Modules\Catalog\Models\Vehicle;
use App\Modules\Submissions\Enums\SubmissionStatus;
use App\Modules\Submissions\Filament\Resources\SubmissionResource\Pages\ListSubmissions;
use App\Modules\Submissions\Models\VehicleSubmission;
use App\Modules\Submissions\Notifications\SubmissionRejected;
use Database\Seeders\RoleSeeder;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Notification;
use Livewire\Livewire;

beforeEach(function () {
    $this->seed(RoleSeeder::class);
    Notification::fake();
});

test('rejecting a submission stores the reason, emails the submitter and creates no vehicle', function () {
    $admin = staff('admin');
    $submission = VehicleSubmission::factory()->create();

    $this->actingAs($admin);

    Livewire::test(ListSubmissions::class)
        ->callTableAction('reject', $submission, data: ['reason' => 'Price is well below market value.']);

    $submission->refresh();

    expect($submission->status)->toBe(SubmissionStatus::Rejected)
        ->and($submission->notes)->toBe('Price is well below market value.')
        ->and($submission->reviewed_by)->toBe($admin->getKey())
        ->and($submission->reviewed_at)->not->toBeNull()
        ->and(Vehicle::query()->count())->toBe(0);

    Notification::assertSentTo($submission, SubmissionRejected::class);
    expect(SubmissionRejected::class)->toImplement(ShouldQueue::class);
});

test('a rejection without a reason is refused and leaves the submission untouched', function () {
    $submission = VehicleSubmission::factory()->create();

    $this->actingAs(staff('admin'));

    Livewire::test(ListSubmissions::class)
        ->callTableAction('reject', $submission, data: ['reason' => ''])
        ->assertHasTableActionErrors(['reason']);

    $submission->refresh();

    expect($submission->status)->toBe(SubmissionStatus::Pending)
        ->and($submission->notes)->toBeNull()
        ->and($submission->reviewed_at)->toBeNull();

    Notification::assertNothingSent();
});

test('approving from the admin table creates exactly one draft vehicle', function () {
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
        'images' => ['submissions/1/aqua-1.jpg'],
    ]);

    $this->actingAs($admin);

    Livewire::test(ListSubmissions::class)
        ->callTableAction('approve', $submission);

    $submission->refresh();

    expect(Vehicle::query()->count())->toBe(1)
        ->and($submission->status)->toBe(SubmissionStatus::Approved)
        ->and($submission->approved_vehicle_id)->not->toBeNull()
        ->and($submission->reviewed_by)->toBe($admin->getKey());
});

test('a reviewer without the submission.review permission cannot act on a submission', function () {
    $submission = VehicleSubmission::factory()->create();

    $this->actingAs(staff('viewer'));

    Livewire::test(ListSubmissions::class)
        ->assertTableActionHidden('reject', $submission)
        ->assertTableActionHidden('approve', $submission);
});

test('the submission edit page renders its submitted values and photos as html', function () {
    $submission = VehicleSubmission::factory()->create([
        'data' => ['make' => 'Toyota', 'model' => 'Aqua'],
        'images' => ['submissions/1/aqua-1.jpg'],
    ]);

    $this->actingAs(staff('admin'))
        ->get("/admin/submissions/{$submission->getKey()}/edit")
        ->assertOk()
        ->assertSee('Toyota', false)
        ->assertSee('<strong>Make</strong>', false)
        ->assertSee('aqua-1.jpg', false);
});
