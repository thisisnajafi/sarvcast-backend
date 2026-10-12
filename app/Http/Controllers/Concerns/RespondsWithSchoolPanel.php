<?php

namespace App\Http\Controllers\Concerns;

use App\Exceptions\SchoolPanelException;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Gate;

trait RespondsWithSchoolPanel
{
    protected function schoolError(SchoolPanelException $exception): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => $exception->getMessage(),
            'error' => $exception->error,
        ], $exception->status);
    }

    protected function denyUnlessSchool(string $ability, mixed $arguments): ?JsonResponse
    {
        $result = Gate::inspect($ability, $arguments);
        if ($result->allowed()) {
            return null;
        }

        return response()->json([
            'success' => false,
            'message' => $result->message() ?: 'دسترسی غیرمجاز.',
            'error' => 'FORBIDDEN',
        ], 403);
    }
}
