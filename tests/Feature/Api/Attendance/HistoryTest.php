<?php

declare(strict_types=1);

use App\Models\Attendance;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->token = $this->user->createToken('test-device')->plainTextToken;
});

it('returns attendance history with pagination', function () {
    Attendance::factory(20)->create(['user_id' => $this->user->id]);

    $response = $this->withToken($this->token)
        ->getJson('/api/v1/attendance/history');

    $response->assertStatus(200)
        ->assertJsonStructure([
            'success', 'message', 'data',
            'meta' => ['current_page', 'last_page', 'per_page', 'total'],
        ])
        ->assertJson(['success' => true]);

    expect($response->json('meta.total'))->toBe(20);
});

it('filters attendance by status', function () {
    Attendance::factory(5)->create(['user_id' => $this->user->id, 'status' => 'present']);
    Attendance::factory(3)->create(['user_id' => $this->user->id, 'status' => 'absent']);

    $response = $this->withToken($this->token)
        ->getJson('/api/v1/attendance/history?status=present');

    $response->assertStatus(200)
        ->assertJson(['success' => true]);

    expect($response->json('meta.total'))->toBe(5);
});

it('returns empty history when no records', function () {
    $response = $this->withToken($this->token)
        ->getJson('/api/v1/attendance/history');

    $response->assertStatus(200)
        ->assertJson(['success' => true, 'data' => []]);
});
