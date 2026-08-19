<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\UserResumeService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class MeResumeController extends Controller
{
    public function __construct(
        private readonly UserResumeService $resumes,
    ) {}

    public function show(Request $request): JsonResponse
    {
        $user = $request->user();
        if (! $user instanceof User || ! $this->resumes->canOwnResume($user)) {
            return $this->forbidden();
        }

        $user->load('resume');
        $resume = $this->resumes->firstOrCreateDraft($user, $user->id);
        $user->setRelation('resume', $resume);

        return response()->json([
            'success' => true,
            'data' => $this->ownerPayload($user, $resume),
        ]);
    }

    public function update(Request $request): JsonResponse
    {
        $user = $request->user();
        if (! $user instanceof User || ! $this->resumes->canOwnResume($user)) {
            return $this->forbidden();
        }

        try {
            $normalized = $this->resumes->validateAndNormalize($request->all(), false);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => collect($e->errors())->flatten()->first() ?: 'اطلاعات رزومه نامعتبر است.',
                'errors' => $e->errors(),
            ], 422);
        }

        $user->load('resume');
        $resume = $this->resumes->firstOrCreateDraft($user, $user->id);
        $resume = $this->resumes->applyUpdate($resume, $normalized, $user->id);
        $user->refresh();
        $user->setRelation('resume', $resume);

        return response()->json([
            'success' => true,
            'message' => 'رزومه ذخیره شد.',
            'data' => $this->ownerPayload($user, $resume),
        ]);
    }

    private function ownerPayload(User $user, $resume): array
    {
        return [
            'user' => $this->resumes->publicUserFields($user, false) + [
                'bio' => $user->bio,
                'headline' => $resume->headline,
                'years_of_experience' => $resume->years_of_experience,
            ],
            'resume' => $this->resumes->toOwnerArray($resume),
        ];
    }

    private function forbidden(): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => 'شما مجاز به ویرایش رزومه نیستید.',
            'error' => 'FORBIDDEN',
        ], 403);
    }
}
