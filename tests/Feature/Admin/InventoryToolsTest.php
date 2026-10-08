<?php

use App\Models\User;
use App\Modules\Catalog\Models\Vehicle;
use Database\Seeders\RoleSeeder;

beforeEach(function () {
    $this->seed(RoleSeeder::class);
});

test('the inventory export downloads a spreadsheet for staff', function () {
    $response = $this->actingAs(staff('editor'))
        ->get(route('admin.inventory.export'));

    $response->assertOk();

    expect(strtolower($response->headers->get('content-type', '')))
        ->toContain('application/vnd.openxmlformats-officedocument.spreadsheetml.sheet')
        ->and($response->headers->get('content-disposition', ''))
        ->toContain('attachment');
});

test('the spec sheet downloads a pdf for staff', function () {
    $vehicle = Vehicle::factory()->create();

    $response = $this->actingAs(staff('viewer'))
        ->get(route('admin.vehicles.spec-sheet', $vehicle));

    $response->assertOk();

    expect(strtolower($response->headers->get('content-type', '')))
        ->toContain('application/pdf')
        ->and($response->headers->get('content-disposition', ''))
        ->toContain($vehicle->slug);
});

test('the inventory tools answer with 403 for a non staff user', function () {
    $vehicle = Vehicle::factory()->create();

    $this->actingAs(User::factory()->create())
        ->get(route('admin.inventory.export'))
        ->assertForbidden();

    $this->actingAs(User::factory()->create())
        ->get(route('admin.vehicles.spec-sheet', $vehicle))
        ->assertForbidden();
});

test('the inventory tools bounce guests to the panel login', function () {
    $vehicle = Vehicle::factory()->create();

    $this->get(route('admin.inventory.export'))
        ->assertRedirect('/admin/login');

    $this->get(route('admin.vehicles.spec-sheet', $vehicle))
        ->assertRedirect('/admin/login');
});
