<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\LocalImportAccessService;
use App\Http\Support\AdminApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LocalImportAccessController extends Controller
{
    public function __construct(
        private readonly LocalImportAccessService $accessService,
    ) {}

    /**
     * Issue a long-lived Sanctum token for local → server imports.
     *
     * Protected by LOCAL_IMPORT_BOOTSTRAP_SECRET (no DB needed on client).
     */
    public function bootstrap(Request $request): JsonResponse
    {
        if (! $this->accessService->bootstrapEnabled()) {
            return response()->json([
                'success' => false,
                'message' => 'Local import bootstrap is disabled. Set LOCAL_IMPORT_BOOTSTRAP_SECRET on the server.',
                'error' => 'BOOTSTRAP_DISABLED',
            ], 503);
        }

        $provided = $request->header('X-Local-Import-Bootstrap')
            ?? $request->input('bootstrap_secret');

        if (! $this->accessService->validateBootstrapSecret(is_string($provided) ? $provided : null)) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid bootstrap secret.',
                'error' => 'BOOTSTRAP_FORBIDDEN',
            ], 403);
        }

        try {
            $userId = $request->filled('user_id') ? $request->integer('user_id') : null;
            $phoneInput = $request->input('phone');
            $phone = is_string($phoneInput) && $phoneInput !== '' ? $phoneInput : null;

            $issued = $this->accessService->issueToken(
                userId: $userId,
                phone: $phone,
                revokeExisting: true,
            );
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
                'error' => 'BOOTSTRAP_FAILED',
            ], 422);
        }

        return AdminApiResponse::success([
            'token_name' => $issued['token_name'],
            'token' => $issued['plain_text_token'],
            'user_id' => $issued['user']->id,
            'phone_number' => $issued['user']->phone_number,
            'abilities' => $issued['abilities'],
            'api_base_url' => rtrim((string) config('app.url'), '/') . '/api/admin',
            'local_env' => [
                'LOCAL_IMPORT_API_BASE_URL' => rtrim((string) config('app.url'), '/') . '/api/admin',
                'LOCAL_IMPORT_API_TOKEN' => $issued['plain_text_token'],
            ],
            'note' => 'Store the token locally; it is only returned once. Revoke by re-bootstrapping or deleting the Sanctum token on the server.',
        ], 'Local import API token issued.', 201);
    }

    /**
     * Rotate token while already authenticated as super_admin.
     */
    public function create(Request $request): JsonResponse
    {
        $user = $request->user();
        if ($user === null || ! $user->isSuperAdmin()) {
            return response()->json([
                'success' => false,
                'message' => 'Super admin required.',
                'error' => 'FORBIDDEN',
            ], 403);
        }

        try {
            $issued = $this->accessService->issueToken(
                userId: $user->id,
                revokeExisting: $request->boolean('revoke_existing', true),
            );
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
                'error' => 'TOKEN_CREATE_FAILED',
            ], 422);
        }

        return AdminApiResponse::success([
            'token_name' => $issued['token_name'],
            'token' => $issued['plain_text_token'],
            'user_id' => $issued['user']->id,
            'abilities' => $issued['abilities'],
            'api_base_url' => rtrim((string) config('app.url'), '/') . '/api/admin',
        ], 'Local import API token rotated.', 201);
    }

    /**
     * Ping endpoint for local machines to validate LOCAL_IMPORT_API_TOKEN.
     */
    public function verify(Request $request): JsonResponse
    {
        $user = $request->user();
        if ($user === null) {
            return response()->json([
                'success' => false,
                'message' => 'Authentication is required.',
                'error' => 'UNAUTHENTICATED',
            ], 401);
        }

        return AdminApiResponse::success(
            $this->accessService->verifyPayload($user),
            'Local import API access verified.',
        );
    }
}
