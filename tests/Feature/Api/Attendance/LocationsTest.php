<?php

declare(strict_types=1);

use App\Models\OfficeLocation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('returns list of active office locations', function () {
    $user = User::factory()->create();
    $token = $user->createToken('test-device')->plainTextToken;

    OfficeLocation::factory(3)->create(['company_id' => $user->company_id, 'is_active' => true]);
    OfficeLocation::factory(2)->create(['company_id' => $user->company_id, 'is_active' => false]);

    $response = $this->withToken($token)
        ->getJson('/api/v1/attendance/locations');

    $response->assertStatus(200)
        ->assertJsonStructure(['success', 'data' => [['id', 'name', 'latitude', 'longitude']]])
        ->assertJson(['success' => true]);

    expect($response->json('data'))->toHaveCount(3);
});

it('returns empty array when no locations', function () {
    $user = User::factory()->create();
    $token = $user->createToken('test-device')->plainTextToken;

    $response = $this->withToken($token)
        ->getJson('/api/v1/attendance/locations');

    $response->assertStatus(200)
        ->assertJson(['success' => true, 'data' => []]);
});
