<?php

namespace App\Http\Support;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\JsonResponse;

/**
 * Standard JSON envelope for dashboard admin APIs (see docs/next-dashboard/03-backend-api-standardization.md).
 */
final class AdminApiResponse
{
    public static function success(mixed $data, ?string $message = null, int $status = 200, array $extra = []): JsonResponse
    {
        $body = array_merge([
            'success' => true,
            'data' => $data,
        ], $extra);

        if ($message !== null) {
            $body['message'] = $message;
        }

        return response()->json($body, $status);
    }

    public static function okMessage(string $message, int $status = 200): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => $message,
        ], $status);
    }

    public static function paginated(LengthAwarePaginator $paginator): JsonResponse
    {
        $meta = [
            'page' => $paginator->currentPage(),
            'perPage' => $paginator->perPage(),
            'total' => $paginator->total(),
            'lastPage' => $paginator->lastPage(),
        ];

        return response()->json([
            'success' => true,
            'data' => $paginator->items(),
            'meta' => $meta,
            'pagination' => [
                'current_page' => $meta['page'],
                'last_page' => $meta['lastPage'],
                'per_page' => $meta['perPage'],
                'total' => $meta['total'],
            ],
        ]);
    }
}
