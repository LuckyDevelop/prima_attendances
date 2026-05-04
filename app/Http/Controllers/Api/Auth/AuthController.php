<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Auth;

use App\Enums\UserStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Auth\LoginRequest;
use App\Http\Resources\Api\UserResource;
use App\Models\AuditLog;
use App\Repositories\Contracts\UserRepositoryInterface;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;

class AuthController extends Controller
{
    use ApiResponse;

    public function __construct(
        private readonly UserRepositoryInterface $userRepository,
    ) {}

    public function login(LoginRequest $request): JsonResponse
    {
        $user = $this->userRepository->findByEmail($request->email);

        if ($user === null || !Hash::check($request->password, $user->password)) {
            return $this->error('Email atau password salah.', 401);
        }

        if ($user->status !== UserStatus::ACTIVE) {
            return $this->error('Akun Anda tidak aktif. Silakan hubungi administrator.', 403);
        }

        DB::beginTransaction();
        try {
            $token = $user->createToken($request->device_name);

            $this->userRepository->updateOrCreateDevice($user, $request->validated());

            AuditLog::create([
                'user_id'    => $user->id,
                'action'     => 'login',
                'entity'     => 'User',
                'entity_id'  => (string) $user->id,
                'ip_address' => $request->ip(),
            ]);

            DB::commit();

            return $this->success([
                'token'      => $token->plainTextToken,
                'token_type' => 'Bearer',
                'expires_at' => now()->addDays(30)->format('Y-m-d H:i:s'),
                'user'       => new UserResource($user),
            ], 'Login berhasil.');

        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Login failed', [
                'email' => $request->email,
                'error' => $e->getMessage(),
            ]);

            return $this->serverError();
        }
    }

    public function me(Request $request): JsonResponse
    {
        $user = $this->userRepository->findById($request->user()->id);

        return $this->success(new UserResource($user), 'Data user berhasil diambil.');
    }

    public function logout(Request $request): JsonResponse
    {
        try {
            $userId = $request->user()->id;

            $request->user()->currentAccessToken()->delete();

            AuditLog::create([
                'user_id'    => $userId,
                'action'     => 'logout',
                'entity'     => 'User',
                'entity_id'  => (string) $userId,
                'ip_address' => $request->ip(),
            ]);

            return $this->success(null, 'Logout berhasil.');

        } catch (\Exception $e) {
            Log::error('Logout failed', [
                'user_id' => $request->user()->id,
                'error'   => $e->getMessage(),
            ]);

            return $this->serverError();
        }
    }
}
