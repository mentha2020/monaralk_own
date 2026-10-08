<?php

use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Auth\Events\PasswordResetLinkSent;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Password;

beforeEach(function () {
    $this->seed(RoleSeeder::class);
});

test('the sign in page is branded and supports dark mode', function () {
    $this->get('/login')
        ->assertOk()
        ->assertSee('Monaralk')
        ->assertSee('theme-toggle')
        ->assertSee('monaralk-theme');
});

test('the registration page is branded and supports dark mode', function () {
    $this->get('/register')
        ->assertOk()
        ->assertSee('Monaralk')
        ->assertSee('theme-toggle');
});

test('the forgot password form renders', function () {
    $this->get('/forgot-password')
        ->assertOk()
        ->assertSee('theme-toggle');
});

test('the reset password form renders for a valid token', function () {
    $user = User::factory()->create();
    $token = Password::broker()->createToken($user);

    $this->get(route('password.reset', ['token' => $token, 'email' => $user->email]))
        ->assertOk()
        ->assertSee('theme-toggle');
});

test('requesting a password reset link dispatches the branded notification', function () {
    Event::fake([PasswordResetLinkSent::class]);

    $user = User::factory()->create();

    $this->post('/forgot-password', ['email' => $user->email])
        ->assertRedirect()->assertSessionHasNoErrors();

    Event::assertDispatched(PasswordResetLinkSent::class);
});

test('the email verification notice renders for an unverified user', function () {
    $user = User::factory()->create(['email_verified_at' => null]);

    $this->actingAs($user)
        ->get(route('verification.notice'))
        ->assertOk()
        ->assertSee('theme-toggle');
});

test('the signed in dashboard is branded and toggles dark mode', function () {
    $this->actingAs(staff('viewer'))
        ->get('/dashboard')
        ->assertOk()
        ->assertSee('Monaralk')
        ->assertSee('data-theme-toggle');
});
