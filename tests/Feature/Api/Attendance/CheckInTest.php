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
    $this->company = $this->user->company;

    $this->officeLocation = OfficeLocation::factory()->create([
        'company_id'   => $this->company->id,
        'latitude'     => -3.5952,
        'longitude'    => 98.6722,
        'radius_meters' => 100,
    ]);

    $this->checkInPayload = fn (array $override = []) => array_merge([
        'office_location_id' => $this->officeLocation->id,
        'latitude'           => -3.5952,
        'longitude'          => 98.6722,
        'is_mock_location'   => false,
        'face_verified'      => true,
        'selfie'             => UploadedFile::fake()->image('selfie.jpg', 100, 100),
        'notes'              => null,
    ], $override);
});

it('returns success on valid check-in', function () {
    $response = $this->withToken($this->token)
        ->postJson('/api/v1/attendance/check-in', ($this->checkInPayload)());

    $response->assertStatus(201)
        ->assertJsonStructure([
            'success', 'message',
            'data' => ['id', 'work_date', 'status', 'check_in'],
        ])
        ->assertJson(['success' => true]);

    expect(Attendance::count())->toBe(1);
});

it('returns 422 when already checked in today', function () {
    Attendance::factory()->create(['user_id' => $this->user->id]);

    $response = $this->withToken($this->token)
        ->postJson('/api/v1/attendance/check-in', ($this->checkInPayload)());

    $response->assertStatus(422)
        ->assertJson(['success' => false]);
});

it('returns 422 when location is outside radius', function () {
    $response = $this->withToken($this->token)
        ->postJson('/api/v1/attendance/check-in', ($this->checkInPayload)([
            'latitude'  => 0,
            'longitude' => 0,
        ]));

    $response->assertStatus(422)
        ->assertJson(['success' => false]);
});

it('returns 422 when required fields are missing', function () {
    $response = $this->withToken($this->token)
        ->postJson('/api/v1/attendance/check-in', []);

    $response->assertStatus(422)
        ->assertJsonStructure(['errors']);
});

it('returns 401 without token', function () {
    $this->postJson('/api/v1/attendance/check-in', ($this->checkInPayload)())
        ->assertStatus(401);
});

it('accepts mock location with warning', function () {
    $response = $this->withToken($this->token)
        ->postJson('/api/v1/attendance/check-in', ($this->checkInPayload)([
            'is_mock_location' => true,
        ]));

    $response->assertStatus(201);
    expect(Attendance::first()->is_mock_location)->toBeTrue();
});
