<?php

use Database\Seeders\RoleSeeder;

beforeEach(function () {
    $this->seed(RoleSeeder::class);
});

test('every taxonomy list page renders for an editor', function (string $uri) {
    $this->actingAs(staff('editor'))
        ->get($uri)
        ->assertOk();
})->with([
    '/admin/makes',
    '/admin/vehicle-models',
    '/admin/body-types',
    '/admin/fuel-types',
    '/admin/transmissions',
    '/admin/colors',
    '/admin/features',
]);

test('every taxonomy create page renders for an editor', function (string $uri) {
    $this->actingAs(staff('editor'))
        ->get($uri)
        ->assertOk();
})->with([
    '/admin/makes/create',
    '/admin/vehicle-models/create',
    '/admin/body-types/create',
    '/admin/fuel-types/create',
    '/admin/transmissions/create',
    '/admin/colors/create',
    '/admin/features/create',
]);

test('a viewer may read the taxonomy but not create it', function () {
    $this->actingAs(staff('viewer'))
        ->get('/admin/makes')
        ->assertOk();

    $this->actingAs(staff('viewer'))
        ->get('/admin/makes/create')
        ->assertForbidden();
});
