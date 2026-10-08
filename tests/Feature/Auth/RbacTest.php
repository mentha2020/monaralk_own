<?php

use App\Models\User;
use App\Modules\Catalog\Models\Vehicle;
use Database\Seeders\RoleSeeder;
use Illuminate\Support\Facades\Route;
use Spatie\Permission\Middleware\PermissionMiddleware;
use Spatie\Permission\Middleware\RoleMiddleware;
use Spatie\Permission\Middleware\RoleOrPermissionMiddleware;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    $this->seed(RoleSeeder::class);
});

test('the permission package middleware aliases are registered', function () {
    $aliases = $this->app['router']->getMiddleware();

    expect($aliases['role'])->toBe(RoleMiddleware::class)
        ->and($aliases['permission'])->toBe(PermissionMiddleware::class)
        ->and($aliases['role_or_permission'])->toBe(RoleOrPermissionMiddleware::class);
});

test('the staff role list matches the seeded roles', function () {
    expect(User::STAFF_ROLES)->toBe(['super_admin', 'admin', 'editor', 'viewer']);

    foreach (User::STAFF_ROLES as $role) {
        expect(Role::where('name', $role)->exists())->toBeTrue();
    }
});

test('guests are redirected from the admin panel to the panel login', function () {
    $this->get('/admin')->assertRedirect('/admin/login');
});

test('every staff role can access the admin panel', function (string $role) {
    $this->actingAs(staff($role))
        ->get('/admin')
        ->assertOk();
})->with(User::STAFF_ROLES);

test('an authenticated non-staff user is forbidden from the admin panel', function () {
    $user = User::factory()->create();

    expect($user->isStaff())->toBeFalse();

    $this->actingAs($user)
        ->get('/admin')
        ->assertForbidden();
});

test('the role middleware alias allows matching roles and rejects the rest', function () {
    Route::middleware(['web', 'role:editor'])->get('/_rbac-role', fn () => response('ok'));

    $this->actingAs(staff('editor'))->get('/_rbac-role')->assertOk();
    $this->actingAs(staff('viewer'))->get('/_rbac-role')->assertForbidden();
    $this->actingAs(User::factory()->create())->get('/_rbac-role')->assertForbidden();
});

test('stacking auth before the role middleware sends guests to login', function () {
    Route::middleware(['web', 'auth', 'role:editor'])->get('/_rbac-role-guest', fn () => response('ok'));

    $this->get('/_rbac-role-guest')->assertRedirect('/login');
});

test('the role middleware alone answers guests with 403 rather than a redirect', function () {
    Route::middleware(['web', 'role:editor'])->get('/_rbac-role-bare', fn () => response('ok'));

    $this->get('/_rbac-role-bare')->assertForbidden();
});

test('the permission middleware alias enforces granular permissions', function () {
    Route::middleware(['web', 'permission:vehicle.delete'])->get('/_rbac-permission', fn () => response('ok'));

    $this->actingAs(staff('admin'))->get('/_rbac-permission')->assertOk();
    $this->actingAs(staff('editor'))->get('/_rbac-permission')->assertForbidden();
    $this->actingAs(staff('super_admin'))->get('/_rbac-permission')->assertOk();
});

test('an unauthorized ability on a route returns 403', function () {
    Route::middleware(['web', 'can:publish,vehicle'])->get('/_rbac-publish/{vehicle}', fn (Vehicle $vehicle) => response('ok'));

    $vehicle = Vehicle::factory()->create();

    $this->actingAs(staff('admin'))
        ->get('/_rbac-publish/'.$vehicle->slug)
        ->assertOk();

    $this->actingAs(staff('editor'))
        ->get('/_rbac-publish/'.$vehicle->slug)
        ->assertForbidden();
});

test('a guest hitting a policy protected route is sent to login', function () {
    Route::middleware(['web', 'auth', 'can:publish,vehicle'])->get('/_rbac-publish-guest/{vehicle}', fn (Vehicle $vehicle) => response('ok'));

    $vehicle = Vehicle::factory()->create();

    $this->get('/_rbac-publish-guest/'.$vehicle->slug)->assertRedirect('/login');
});
