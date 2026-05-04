<?php

declare(strict_types=1);

use App\Models\Company;
use App\Models\Device;
use App\Models\User;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

// ─── Index ────────────────────────────────────────────────────────────────────

it('returns list of user devices', function () {
    $company = Company::factory()->create();
    $user    = User::factory()->create(['company_id' => $company->id]);
    $token   = $user->createToken('test')->plainTextToken;

    Device::create([
        'user_id'     => $user->id,
        'device_id'   => 'android-abc',
        'device_name' => 'Samsung Galaxy',
        'platform'    => 'android',
        'is_active'   => true,
    ]);

    $response = $this->withToken($token)
        ->getJson('/api/v1/devices')
        ->assertOk();

    expect($response->json('data'))->toHaveCount(1);
});

// ─── Register ─────────────────────────────────────────────────────────────────

it('registers a new device', function () {
    $company = Company::factory()->create();
    $user    = User::factory()->create(['company_id' => $company->id]);
    $token   = $user->createToken('test')->plainTextToken;

    $this->withToken($token)
        ->postJson('/api/v1/devices/register', [
            'device_id'   => 'android-uuid-123',
            'device_name' => 'Samsung Galaxy S23',
            'platform'    => 'android',
            'fcm_token'   => 'fcm-token-abc',
        ])
        ->assertStatus(201)
        ->assertJsonPath('success', true);

    $this->assertDatabaseHas('devices', [
        'user_id'   => $user->id,
        'device_id' => 'android-uuid-123',
    ]);
});

it('updates existing device on re-register', function () {
    $company = Company::factory()->create();
    $user    = User::factory()->create(['company_id' => $company->id]);
    $token   = $user->createToken('test')->plainTextToken;

    Device::create([
        'user_id'     => $user->id,
        'device_id'   => 'android-uuid-123',
        'device_name' => 'Old Name',
        'platform'    => 'android',
        'fcm_token'   => 'old-token',
        'is_active'   => true,
    ]);

    $this->withToken($token)
        ->postJson('/api/v1/devices/register', [
            'device_id'   => 'android-uuid-123',
            'device_name' => 'New Name',
            'platform'    => 'android',
            'fcm_token'   => 'new-token',
        ])
        ->assertStatus(201);

    expect(Device::where('user_id', $user->id)->count())->toBe(1)
        ->and(Device::where('user_id', $user->id)->first()->device_name)->toBe('New Name');
});

it('returns 422 for invalid platform', function () {
    $company = Company::factory()->create();
    $user    = User::factory()->create(['company_id' => $company->id]);
    $token   = $user->createToken('test')->plainTextToken;

    $this->withToken($token)
        ->postJson('/api/v1/devices/register', [
            'device_id'   => 'abc',
            'device_name' => 'Device',
            'platform'    => 'windows',
        ])
        ->assertStatus(422);
});

// ─── Update FCM Token ─────────────────────────────────────────────────────────

it('updates fcm token for existing device', function () {
    $company = Company::factory()->create();
    $user    = User::factory()->create(['company_id' => $company->id]);
    $token   = $user->createToken('test')->plainTextToken;

    Device::create([
        'user_id'     => $user->id,
        'device_id'   => 'android-uuid-123',
        'device_name' => 'Test Device',
        'platform'    => 'android',
        'fcm_token'   => 'old-token',
        'is_active'   => true,
    ]);

    $this->withToken($token)
        ->putJson('/api/v1/devices/fcm-token', [
            'device_id' => 'android-uuid-123',
            'fcm_token' => 'new-fcm-token',
        ])
        ->assertOk();

    expect(Device::where('user_id', $user->id)->first()->fcm_token)->toBe('new-fcm-token');
});

// ─── Destroy ─────────────────────────────────────────────────────────────────

it('deactivates a device', function () {
    $company = Company::factory()->create();
    $user    = User::factory()->create(['company_id' => $company->id]);
    $token   = $user->createToken('test')->plainTextToken;

    $device = Device::create([
        'user_id'     => $user->id,
        'device_id'   => 'android-uuid-123',
        'device_name' => 'Test Device',
        'platform'    => 'android',
        'is_active'   => true,
    ]);

    $this->withToken($token)
        ->deleteJson("/api/v1/devices/{$device->id}")
        ->assertOk();

    expect($device->fresh()->is_active)->toBeFalse();
});

it('returns 404 for another users device', function () {
    $company = Company::factory()->create();
    $user    = User::factory()->create(['company_id' => $company->id]);
    $other   = User::factory()->create(['company_id' => $company->id]);
    $token   = $user->createToken('test')->plainTextToken;

    $device = Device::create([
        'user_id'     => $other->id,
        'device_id'   => 'android-uuid-123',
        'device_name' => 'Other Device',
        'platform'    => 'android',
        'is_active'   => true,
    ]);

    $this->withToken($token)
        ->deleteJson("/api/v1/devices/{$device->id}")
        ->assertStatus(404);
});
