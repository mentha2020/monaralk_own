<?php

use App\Models\User;
use Database\Seeders\RoleSeeder;

beforeEach(function () {
    $this->seed(RoleSeeder::class);
});

test('the enquiries list is readable by everyone who holds enquiry.view', function (string $role) {
    $this->actingAs(staff($role))
        ->get('/admin/enquiries')
        ->assertOk();
})->with(['super_admin', 'admin', 'editor', 'viewer']);

test('the submissions list is readable by everyone who holds submission.view', function (string $role) {
    $this->actingAs(staff($role))
        ->get('/admin/submissions')
        ->assertOk();
})->with(['super_admin', 'admin', 'editor', 'viewer']);

test('the users screen follows the user permissions', function () {
    $this->actingAs(staff('super_admin'))->get('/admin/users')->assertOk();
    $this->actingAs(staff('viewer'))->get('/admin/users')->assertOk();

    $this->actingAs(staff('viewer'))->get('/admin/users/create')->assertForbidden();
    $this->actingAs(staff('admin'))->get('/admin/users/create')->assertOk();
});

test('roles are reserved for the super admin', function () {
    $this->actingAs(staff('super_admin'))->get('/admin/roles')->assertOk();

    $this->actingAs(staff('admin'))->get('/admin/roles')->assertForbidden();
    $this->actingAs(staff('editor'))->get('/admin/roles')->assertForbidden();
});

test('settings follow the setting permission', function () {
    $this->actingAs(staff('admin'))->get('/admin/settings')->assertOk();

    $this->actingAs(staff('editor'))->get('/admin/settings')->assertForbidden();
    $this->actingAs(staff('viewer'))->get('/admin/settings')->assertForbidden();
});

test('pages are editable by the roles that hold page.manage', function () {
    $this->actingAs(staff('editor'))->get('/admin/pages')->assertOk();
    $this->actingAs(staff('admin'))->get('/admin/pages')->assertOk();

    $this->actingAs(staff('viewer'))->get('/admin/pages')->assertForbidden();
});

test('the dashboard renders its stat widgets for staff', function () {
    $response = $this->actingAs(staff('editor'))->get('/admin');

    $response->assertOk();

    expect($response->getContent())
        ->toContain('Published')
        ->toContain('New enquiries')
        ->toContain('Pending submissions');
});

test('a non staff user is still locked out of the dashboard', function () {
    $this->actingAs(User::factory()->create())
        ->get('/admin')
        ->assertForbidden();
});
