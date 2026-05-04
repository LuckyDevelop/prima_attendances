<?php

declare(strict_types=1);

use App\Enums\NotificationType;
use App\Models\Company;
use App\Models\Notification;
use App\Models\User;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

// ─── Index ────────────────────────────────────────────────────────────────────

it('returns paginated notifications for user', function () {
    $company = Company::factory()->create();
    $user    = User::factory()->create(['company_id' => $company->id]);
    $token   = $user->createToken('test')->plainTextToken;

    Notification::factory()->count(3)->create(['user_id' => $user->id, 'is_read' => false]);

    $response = $this->withToken($token)
        ->getJson('/api/v1/notifications')
        ->assertOk()
        ->assertJsonStructure([
            'data', 'meta', 'unread_count',
        ]);

    expect($response->json('unread_count'))->toBe(3);
});

it('filters unread notifications', function () {
    $company = Company::factory()->create();
    $user    = User::factory()->create(['company_id' => $company->id]);
    $token   = $user->createToken('test')->plainTextToken;

    Notification::factory()->create(['user_id' => $user->id, 'is_read' => false]);
    Notification::factory()->create(['user_id' => $user->id, 'is_read' => true]);

    $response = $this->withToken($token)
        ->getJson('/api/v1/notifications?is_read=false')
        ->assertOk();

    expect($response->json('data'))->toHaveCount(1);
});

it('does not return other users notifications', function () {
    $company  = Company::factory()->create();
    $user     = User::factory()->create(['company_id' => $company->id]);
    $other    = User::factory()->create(['company_id' => $company->id]);
    $token    = $user->createToken('test')->plainTextToken;

    Notification::factory()->create(['user_id' => $other->id]);

    $response = $this->withToken($token)
        ->getJson('/api/v1/notifications')
        ->assertOk();

    expect($response->json('data'))->toHaveCount(0);
});

// ─── Unread Count ─────────────────────────────────────────────────────────────

it('returns unread count', function () {
    $company = Company::factory()->create();
    $user    = User::factory()->create(['company_id' => $company->id]);
    $token   = $user->createToken('test')->plainTextToken;

    Notification::factory()->count(5)->create(['user_id' => $user->id, 'is_read' => false]);
    Notification::factory()->count(2)->create(['user_id' => $user->id, 'is_read' => true]);

    $response = $this->withToken($token)
        ->getJson('/api/v1/notifications/unread-count')
        ->assertOk();

    expect($response->json('data.unread_count'))->toBe(5);
});

// ─── Mark as Read ─────────────────────────────────────────────────────────────

it('marks a notification as read', function () {
    $company      = Company::factory()->create();
    $user         = User::factory()->create(['company_id' => $company->id]);
    $notification = Notification::factory()->create(['user_id' => $user->id, 'is_read' => false]);
    $token        = $user->createToken('test')->plainTextToken;

    $this->withToken($token)
        ->postJson("/api/v1/notifications/{$notification->id}/read")
        ->assertOk();

    expect($notification->fresh()->is_read)->toBeTrue();
});

it('returns 404 when marking another users notification as read', function () {
    $company      = Company::factory()->create();
    $user         = User::factory()->create(['company_id' => $company->id]);
    $other        = User::factory()->create(['company_id' => $company->id]);
    $notification = Notification::factory()->create(['user_id' => $other->id]);
    $token        = $user->createToken('test')->plainTextToken;

    $this->withToken($token)
        ->postJson("/api/v1/notifications/{$notification->id}/read")
        ->assertStatus(404);
});

// ─── Mark All as Read ─────────────────────────────────────────────────────────

it('marks all notifications as read', function () {
    $company = Company::factory()->create();
    $user    = User::factory()->create(['company_id' => $company->id]);
    $token   = $user->createToken('test')->plainTextToken;

    Notification::factory()->count(4)->create(['user_id' => $user->id, 'is_read' => false]);

    $response = $this->withToken($token)
        ->postJson('/api/v1/notifications/read-all')
        ->assertOk();

    expect($response->json('data.marked_count'))->toBe(4)
        ->and(Notification::where('user_id', $user->id)->where('is_read', false)->count())->toBe(0);
});
