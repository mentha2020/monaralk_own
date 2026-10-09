<?php

use App\Livewire\SubmitVehicleWizard;
use App\Modules\Submissions\Models\VehicleSubmission;
use Livewire\Livewire;

test('repeated failed logins hit the rate limiter and get the branded page', function () {
    foreach (range(1, 5) as $attempt) {
        $this->post('/login', [
            'email' => 'nobody@example.lk',
            'password' => 'wrong-password',
        ])->assertRedirect();
    }

    $response = $this->post('/login', [
        'email' => 'nobody@example.lk',
        'password' => 'wrong-password',
    ]);

    $response->assertStatus(429)
        ->assertHeader('X-Content-Type-Options', 'nosniff')
        ->assertSee('Too many requests', false);
});

test('the submit wizard stores nothing when the hidden honeypot field is filled', function () {
    Livewire::test(SubmitVehicleWizard::class)
        ->set('website', 'https://spam.example')
        ->call('submit')
        ->assertSet('step', 6)
        ->assertHasNoErrors();

    expect(VehicleSubmission::query()->count())->toBe(0);
});

test('the submit wizard rate limits repeated submissions', function () {
    $wizard = Livewire::test(SubmitVehicleWizard::class);

    foreach (range(1, 5) as $attempt) {
        $wizard->call('submit')
            ->assertHasErrors('form.make_id')
            ->assertHasNoErrors('form');
    }

    $wizard->call('submit')->assertHasErrors('form');

    expect(VehicleSubmission::query()->count())->toBe(0);
});
