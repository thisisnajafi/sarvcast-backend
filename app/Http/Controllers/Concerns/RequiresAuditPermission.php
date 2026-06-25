<?php

namespace App\Http\Controllers\Concerns;

use Illuminate\Http\JsonResponse;

trait RequiresAuditPermission
{
    protected function ensureAuditView(): ?JsonResponse
    {
        $user = auth('sanctum')->user();
        if (! $user) {
            return response()->json([
                'success' => false,
                'message' => 'Authentication is required.',
                'error' => 'UNAUTHENTICATED',
            ], 401);
        }

        if (method_exists($user, 'isSuperAdmin') && $user->isSuperAdmin()) {
            return null;
        }

        if (method_exists($user, 'hasPermission') && $user->hasPermission('audit.view')) {
            return null;
        }

        return response()->json([
            'success' => false,
            'message' => 'You do not have permission to view activity logs.',
            'error' => 'FORBIDDEN',
            'required_permission' => 'audit.view',
        ], 403);
    }

    protected function ensureAuditExport(): ?JsonResponse
    {
        if ($response = $this->ensureAuditView()) {
            return $response;
        }

        $user = auth('sanctum')->user();
        if (method_exists($user, 'isSuperAdmin') && $user->isSuperAdmin()) {
            return null;
        }

        if (method_exists($user, 'hasPermission') && $user->hasPermission('audit.export')) {
            return null;
        }

        return response()->json([
            'success' => false,
            'message' => 'You do not have permission to export activity logs.',
            'error' => 'FORBIDDEN',
            'required_permission' => 'audit.export',
        ], 403);
    }
}
