<?php

declare(strict_types=1);

use App\Models\Company;
use App\Models\Holiday;
use App\Models\User;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

it('returns holidays for the current year', function () {
    $company = Company::factory()->create();
    $user    = User::factory()->create(['company_id' => $company->id]);
    $token   = $user->createToken('test')->plainTextToken;

    Holiday::create([
        'company_id'  => $company->id,
        'date'        => '2026-05-01',
        'name'        => 'Hari Buruh',
        'is_national' => true,
    ]);

    $response = $this->withToken($token)
        ->getJson('/api/v1/holidays?year=2026')
        ->assertOk()
        ->assertJsonStructure(['data' => [['id', 'date', 'name', 'is_national']]]);

    expect($response->json('data'))->toHaveCount(1);
});

it('filters holidays by month', function () {
    $company = Company::factory()->create();
    $user    = User::factory()->create(['company_id' => $company->id]);
    $token   = $user->createToken('test')->plainTextToken;

    Holiday::create(['company_id' => $company->id, 'date' => '2026-05-01', 'name' => 'Hari Buruh', 'is_national' => true]);
    Holiday::create(['company_id' => $company->id, 'date' => '2026-06-01', 'name' => 'Pancasila', 'is_national' => true]);

    $response = $this->withToken($token)
        ->getJson('/api/v1/holidays?year=2026&month=5')
        ->assertOk();

    expect($response->json('data'))->toHaveCount(1)
        ->and($response->json('data.0.name'))->toBe('Hari Buruh');
});

it('returns national holidays regardless of company', function () {
    $company1 = Company::factory()->create();
    $company2 = Company::factory()->create();
    $user     = User::factory()->create(['company_id' => $company1->id]);
    $token    = $user->createToken('test')->plainTextToken;

    Holiday::create(['company_id' => $company2->id, 'date' => '2026-05-01', 'name' => 'Hari Buruh', 'is_national' => true]);

    $response = $this->withToken($token)
        ->getJson('/api/v1/holidays?year=2026')
        ->assertOk();

    expect($response->json('data'))->toHaveCount(1);
});

it('does not return other company non-national holidays', function () {
    $company1 = Company::factory()->create();
    $company2 = Company::factory()->create();
    $user     = User::factory()->create(['company_id' => $company1->id]);
    $token    = $user->createToken('test')->plainTextToken;

    Holiday::create(['company_id' => $company2->id, 'date' => '2026-05-15', 'name' => 'Company Day', 'is_national' => false]);

    $response = $this->withToken($token)
        ->getJson('/api/v1/holidays?year=2026')
        ->assertOk();

    expect($response->json('data'))->toHaveCount(0);
});

it('returns 401 for unauthenticated request', function () {
    $this->getJson('/api/v1/holidays')->assertStatus(401);
});
