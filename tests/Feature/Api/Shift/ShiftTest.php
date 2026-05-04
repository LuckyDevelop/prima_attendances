<?php

declare(strict_types=1);

use App\Models\Company;
use App\Models\Shift;
use App\Models\ShiftAssignment;
use App\Models\User;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

// ─── Today ────────────────────────────────────────────────────────────────────

it('returns null when no shift is assigned today', function () {
    $company = Company::factory()->create();
    $user    = User::factory()->create(['company_id' => $company->id]);
    $token   = $user->createToken('test')->plainTextToken;

    $response = $this->withToken($token)
        ->getJson('/api/v1/shifts/today')
        ->assertOk();

    expect($response->json('data'))->toBeNull();
});

it('returns shift when assigned today', function () {
    $company = Company::factory()->create();
    $user    = User::factory()->create(['company_id' => $company->id]);
    $token   = $user->createToken('test')->plainTextToken;

    $shift = Shift::create([
        'company_id'    => $company->id,
        'name'          => 'Shift Pagi',
        'start_time'    => '08:00:00',
        'end_time'      => '17:00:00',
        'break_minutes' => 60,
    ]);

    ShiftAssignment::create([
        'user_id'   => $user->id,
        'shift_id'  => $shift->id,
        'work_date' => today()->toDateString(),
    ]);

    $response = $this->withToken($token)
        ->getJson('/api/v1/shifts/today')
        ->assertOk()
        ->assertJsonStructure(['data' => ['id', 'name', 'start_time', 'end_time', 'break_minutes']]);

    expect($response->json('data.name'))->toBe('Shift Pagi');
});

// ─── Schedule ─────────────────────────────────────────────────────────────────

it('returns shift schedule for a given month', function () {
    $company = Company::factory()->create();
    $user    = User::factory()->create(['company_id' => $company->id]);
    $token   = $user->createToken('test')->plainTextToken;

    $shift = Shift::create([
        'company_id'    => $company->id,
        'name'          => 'Shift Pagi',
        'start_time'    => '08:00:00',
        'end_time'      => '17:00:00',
        'break_minutes' => 60,
    ]);

    ShiftAssignment::create(['user_id' => $user->id, 'shift_id' => $shift->id, 'work_date' => '2026-05-01']);
    ShiftAssignment::create(['user_id' => $user->id, 'shift_id' => $shift->id, 'work_date' => '2026-05-02']);

    $response = $this->withToken($token)
        ->getJson('/api/v1/shifts/schedule?month=2026-05')
        ->assertOk();

    expect($response->json('data'))->toHaveCount(2);
});

it('returns 422 for invalid month format', function () {
    $company = Company::factory()->create();
    $user    = User::factory()->create(['company_id' => $company->id]);
    $token   = $user->createToken('test')->plainTextToken;

    $this->withToken($token)
        ->getJson('/api/v1/shifts/schedule?month=May-2026')
        ->assertStatus(422);
});
