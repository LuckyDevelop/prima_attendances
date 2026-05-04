<?php

declare(strict_types=1);

use App\Models\Company;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

// ─── Show ─────────────────────────────────────────────────────────────────────

it('returns profile data for authenticated user', function () {
    $company = Company::factory()->create();
    $user    = User::factory()->create(['company_id' => $company->id]);
    $token   = $user->createToken('test')->plainTextToken;

    $this->withToken($token)
        ->getJson('/api/v1/profile')
        ->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonStructure(['data' => ['id', 'full_name', 'email', 'role']]);
});

it('returns 401 for unauthenticated profile request', function () {
    $this->getJson('/api/v1/profile')->assertStatus(401);
});

// ─── Update ───────────────────────────────────────────────────────────────────

it('updates full_name and phone', function () {
    $company = Company::factory()->create();
    $user    = User::factory()->create(['company_id' => $company->id]);
    $token   = $user->createToken('test')->plainTextToken;

    $this->withToken($token)
        ->putJson('/api/v1/profile', ['full_name' => 'Budi Santoso', 'phone' => '081234567890'])
        ->assertOk()
        ->assertJsonPath('data.full_name', 'Budi Santoso');

    expect($user->fresh()->full_name)->toBe('Budi Santoso')
        ->and($user->fresh()->phone)->toBe('081234567890');
});

it('returns 422 when full_name is missing on update', function () {
    $company = Company::factory()->create();
    $user    = User::factory()->create(['company_id' => $company->id]);
    $token   = $user->createToken('test')->plainTextToken;

    $this->withToken($token)
        ->putJson('/api/v1/profile', ['phone' => '081234567890'])
        ->assertStatus(422);
});

// ─── Change Password ──────────────────────────────────────────────────────────

it('changes password successfully', function () {
    $company = Company::factory()->create();
    $user    = User::factory()->create([
        'company_id' => $company->id,
        'password'   => Hash::make('oldpassword'),
    ]);
    $token = $user->createToken('test')->plainTextToken;

    $this->withToken($token)
        ->putJson('/api/v1/profile/password', [
            'current_password'          => 'oldpassword',
            'new_password'              => 'newpassword123',
            'new_password_confirmation' => 'newpassword123',
        ])
        ->assertOk()
        ->assertJsonPath('success', true);

    expect(Hash::check('newpassword123', $user->fresh()->password))->toBeTrue();
});

it('returns 422 when current password is wrong', function () {
    $company = Company::factory()->create();
    $user    = User::factory()->create([
        'company_id' => $company->id,
        'password'   => Hash::make('oldpassword'),
    ]);
    $token = $user->createToken('test')->plainTextToken;

    $this->withToken($token)
        ->putJson('/api/v1/profile/password', [
            'current_password'          => 'wrongpassword',
            'new_password'              => 'newpassword123',
            'new_password_confirmation' => 'newpassword123',
        ])
        ->assertStatus(422);
});

// ─── Upload Avatar ────────────────────────────────────────────────────────────

it('uploads avatar and returns photo_url', function () {
    Storage::fake('public');

    $company = Company::factory()->create();
    $user    = User::factory()->create(['company_id' => $company->id]);
    $token   = $user->createToken('test')->plainTextToken;

    $file = UploadedFile::fake()->image('avatar.jpg', 200, 200);

    $response = $this->withToken($token)
        ->postJson('/api/v1/profile/avatar', ['photo' => $file])
        ->assertOk()
        ->assertJsonStructure(['data' => ['photo_url']]);

    expect($user->fresh()->photo)->not->toBeNull();
});

it('returns 422 when avatar is not an image', function () {
    $company = Company::factory()->create();
    $user    = User::factory()->create(['company_id' => $company->id]);
    $token   = $user->createToken('test')->plainTextToken;

    $file = UploadedFile::fake()->create('document.pdf', 100, 'application/pdf');

    $this->withToken($token)
        ->postJson('/api/v1/profile/avatar', ['photo' => $file])
        ->assertStatus(422);
});

// ─── Face Embedding ───────────────────────────────────────────────────────────

it('stores face embedding', function () {
    $company   = Company::factory()->create();
    $user      = User::factory()->create(['company_id' => $company->id]);
    $token     = $user->createToken('test')->plainTextToken;
    $embedding = json_encode(array_fill(0, 128, 0.1));

    $this->withToken($token)
        ->postJson('/api/v1/profile/face-embedding', ['embedding' => $embedding])
        ->assertOk()
        ->assertJsonPath('success', true);

    expect($user->fresh()->getRawOriginal('face_embedding'))->not->toBeNull();
});

it('returns 422 when embedding is not valid json', function () {
    $company = Company::factory()->create();
    $user    = User::factory()->create(['company_id' => $company->id]);
    $token   = $user->createToken('test')->plainTextToken;

    $this->withToken($token)
        ->postJson('/api/v1/profile/face-embedding', ['embedding' => 'not-json'])
        ->assertStatus(422);
});
