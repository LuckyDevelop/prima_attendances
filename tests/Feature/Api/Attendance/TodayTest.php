<?php

declare(strict_types=1);

use App\Models\Attendance;
use App\Models\OfficeLocation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->token = $this->user->createToken('test-device')->plainTextToken;

    $this->officeLocation = OfficeLocation::factory()->create([
        'company_id' => $this->user->company_id,
    ]);
});

it('returns empty array when no attendance today', function () {
    $response = $this->withToken($this->token)
        ->getJson('/api/v1/attendance/today');

    $response->assertStatus(200)
        ->assertJsonStructure(['data' => ['work_date', 'can_check_in', 'can_check_out']])
        ->assertJson(['data' => ['can_check_in' => true, 'can_check_out' => false]]);
});

it('returns attendance when already checked in', function () {
    $attendance = Attendance::factory()->create([
        'user_id'            => $this->user->id,
        'office_location_id' => $this->officeLocation->id,
        'check_out_time'     => null,
    ]);

    $response = $this->withToken($this->token)
        ->getJson('/api/v1/attendance/today');

    $response->assertStatus(200)
        ->assertJsonStructure(['data' => ['id', 'status', 'check_in']])
        ->assertJson(['data' => ['can_check_in' => false, 'can_check_out' => true]]);
});
