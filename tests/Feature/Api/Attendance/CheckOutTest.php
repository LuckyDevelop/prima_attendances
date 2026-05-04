<?php

declare(strict_types=1);

use App\Models\Attendance;
use App\Models\OfficeLocation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->token = $this->user->createToken('test-device')->plainTextToken;

    $this->officeLocation = OfficeLocation::factory()->create([
        'company_id'    => $this->user->company_id,
        'latitude'      => -3.5952,
        'longitude'     => 98.6722,
        'radius_meters' => 100,
    ]);

    $this->attendance = Attendance::factory()->create([
        'user_id'            => $this->user->id,
        'office_location_id' => $this->officeLocation->id,
        'check_out_time'     => null,
    ]);

    $this->checkOutPayload = fn () => [
        'latitude'         => -3.5952,
        'longitude'        => 98.6722,
        'is_mock_location' => false,
        'face_verified'    => true,
        'selfie'           => UploadedFile::fake()->image('selfie.jpg', 100, 100),
        'notes'            => null,
    ];
});

it('returns success on valid check-out', function () {
    $response = $this->withToken($this->token)
        ->postJson('/api/v1/attendance/check-out', ($this->checkOutPayload)());

    $response->assertStatus(200)
        ->assertJsonStructure(['success', 'message', 'data' => ['id', 'check_out']])
        ->assertJson(['success' => true]);

    expect($this->attendance->fresh()->check_out_time)->not->toBeNull();
});

it('returns 422 when not checked in today', function () {
    $this->attendance->delete();

    $response = $this->withToken($this->token)
        ->postJson('/api/v1/attendance/check-out', ($this->checkOutPayload)());

    $response->assertStatus(422)
        ->assertJson(['success' => false]);
});

it('returns 422 when already checked out', function () {
    $this->attendance->update(['check_out_time' => now()]);

    $response = $this->withToken($this->token)
        ->postJson('/api/v1/attendance/check-out', ($this->checkOutPayload)());

    $response->assertStatus(422)
        ->assertJson(['success' => false]);
});

it('returns 422 when required fields are missing', function () {
    $this->withToken($this->token)
        ->postJson('/api/v1/attendance/check-out', [])
        ->assertStatus(422)
        ->assertJsonStructure(['errors']);
});
