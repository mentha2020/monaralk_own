<?php

use App\Modules\Catalog\Models\Vehicle;
use Database\Seeders\RoleSeeder;

beforeEach(function () {
    $this->seed(RoleSeeder::class);
});

test('staff roles can open the vehicle list page', function (string $role) {
    $this->actingAs(staff($role))
        ->get('/admin/vehicles')
        ->assertOk();
})->with(['super_admin', 'admin', 'editor', 'viewer']);

test('the vehicle create page is reserved for creators', function () {
    $this->actingAs(staff('editor'))
        ->get('/admin/vehicles/create')
        ->assertOk();

    $this->actingAs(staff('viewer'))
        ->get('/admin/vehicles/create')
        ->assertForbidden();
});

test('the vehicle edit page renders and is guarded by policy', function () {
    $vehicle = Vehicle::factory()->create();

    $this->actingAs(staff('editor'))
        ->get('/admin/vehicles/'.$vehicle->slug.'/edit')
        ->assertOk();

    $this->actingAs(staff('viewer'))
        ->get('/admin/vehicles/'.$vehicle->slug.'/edit')
        ->assertForbidden();
});

test('guests are bounced from the panel to the panel login', function () {
    $this->get('/admin/vehicles')->assertRedirect('/admin/login');
});
