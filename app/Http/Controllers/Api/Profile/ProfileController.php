<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Profile;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Profile\ChangePasswordRequest;
use App\Http\Requests\Api\Profile\FaceEmbeddingRequest;
use App\Http\Requests\Api\Profile\UpdateProfileRequest;
use App\Http\Requests\Api\Profile\UploadAvatarRequest;
use App\Http\Resources\Api\UserResource;
use App\Models\AuditLog;
use App\Repositories\Contracts\UserRepositoryInterface;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class ProfileController extends Controller
{
    use ApiResponse;

    public function __construct(
        private readonly UserRepositoryInterface $userRepository,
    ) {}

    public function show(Request $request): JsonResponse
    {
        $user = $this->userRepository->findById($request->user()->id);

        return $this->success(new UserResource($user), 'Data profil berhasil diambil.');
    }

    public function update(UpdateProfileRequest $request): JsonResponse
    {
        $user = $this->userRepository->update(
            $request->user(),
            $request->only(['full_name', 'phone'])
        );

        AuditLog::create([
            'user_id'    => $user->id,
            'action'     => 'update_profile',
            'entity'     => 'User',
            'entity_id'  => (string) $user->id,
            'ip_address' => $request->ip(),
        ]);

        return $this->success(new UserResource($user), 'Profil berhasil diperbarui.');
    }

    public function changePassword(ChangePasswordRequest $request): JsonResponse
    {
        $user = $request->user();

        $this->userRepository->updatePassword($user, Hash::make($request->new_password));

        AuditLog::create([
            'user_id'    => $user->id,
            'action'     => 'change_password',
            'entity'     => 'User',
            'entity_id'  => (string) $user->id,
            'ip_address' => $request->ip(),
        ]);

        return $this->success(null, 'Password berhasil diperbarui.');
    }

    public function uploadAvatar(UploadAvatarRequest $request): JsonResponse
    {
        $user = $request->user();

        try {
            if ($user->photo) {
                Storage::disk('public')->delete($user->photo);
            }

            $path = $request->file('photo')->store("photos/{$user->id}", 'public');

            $this->userRepository->updatePhoto($user, $path);

            AuditLog::create([
                'user_id'    => $user->id,
                'action'     => 'upload_avatar',
                'entity'     => 'User',
                'entity_id'  => (string) $user->id,
                'ip_address' => $request->ip(),
            ]);

            return $this->success(
                ['photo_url' => asset('storage/' . $path)],
                'Foto profil berhasil diperbarui.'
            );

        } catch (\Exception $e) {
            Log::error('Failed to upload avatar', [
                'user_id' => $user->id,
                'error'   => $e->getMessage(),
            ]);

            return $this->serverError();
        }
    }

    public function faceEmbedding(FaceEmbeddingRequest $request): JsonResponse
    {
        $user = $request->user();

        $this->userRepository->updateFaceEmbedding($user, $request->embedding);

        AuditLog::create([
            'user_id'    => $user->id,
            'action'     => 'update_face_embedding',
            'entity'     => 'User',
            'entity_id'  => (string) $user->id,
            'ip_address' => $request->ip(),
        ]);

        return $this->success(null, 'Data wajah berhasil disimpan.');
    }
}
