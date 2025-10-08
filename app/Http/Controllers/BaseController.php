<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Auth\Access\AuthorizationException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Exception;

abstract class BaseController extends Controller
{
    /**
     * Handle API responses with standardized format
     */
    protected function successResponse($data = null, $message = null, $meta = null, $statusCode = 200): JsonResponse
    {
        $response = ['success' => true];
        
        if ($message) {
            $response['message'] = $message;
        }
        
        if ($data !== null) {
            $response['data'] = $data;
        }
        
        if ($meta) {
            $response['meta'] = $meta;
        }
        
        return response()->json($response, $statusCode);
    }

    /**
     * Handle API error responses with standardized format
     */
    protected function errorResponse($message, $errorCode = null, $statusCode = 400, $data = null): JsonResponse
    {
        $response = [
            'success' => false,
            'message' => $message
        ];
        
        if ($errorCode) {
            $response['error_code'] = $errorCode;
        }
        
        if ($data) {
            $response['data'] = $data;
        }
        
        return response()->json($response, $statusCode);
    }

    /**
     * Handle web responses with flash messages
     */
    protected function webSuccessResponse($message, $redirectUrl = null): RedirectResponse
    {
        if ($redirectUrl) {
            return redirect($redirectUrl)->with('success', $message);
        }
        
        return redirect()->back()->with('success', $message);
    }

    /**
     * Handle web error responses with flash messages
     */
    protected function webErrorResponse($message, $redirectUrl = null): RedirectResponse
    {
        if ($redirectUrl) {
            return redirect($redirectUrl)->with('error', $message);
        }
        
        return redirect()->back()->with('error', $message);
    }

    /**
     * Handle exceptions with proper logging and responses
     */
    protected function handleException(Exception $e, Request $request, $context = []): JsonResponse|RedirectResponse
    {
        $errorId = uniqid('err_');
        $userId = $request->user() ? $request->user()->id : null;
        
        // Log the exception with context
        Log::error('Controller Exception', [
            'error_id' => $errorId,
            'user_id' => $userId,
            'url' => $request->fullUrl(),
            'method' => $request->method(),
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'exception' => $e->getMessage(),
            'file' => $e->getFile(),
            'line' => $e->getLine(),
            'trace' => $e->getTraceAsString(),
            'context' => $context
        ]);

        // Handle specific exception types
        if ($e instanceof ValidationException) {
            if ($request->expectsJson()) {
                return $this->errorResponse(
                    'خطا در اعتبارسنجی داده‌ها',
                    'VALIDATION_ERROR',
                    422,
                    ['validation_errors' => $e->errors()]
                );
            }
            
            return redirect()->back()
                ->withErrors($e->errors())
                ->withInput();
        }

        if ($e instanceof ModelNotFoundException) {
            if ($request->expectsJson()) {
                return $this->errorResponse(
                    'منبع مورد نظر یافت نشد',
                    'NOT_FOUND',
                    404
                );
            }
            
            return $this->webErrorResponse('منبع مورد نظر یافت نشد');
        }

        if ($e instanceof AuthorizationException) {
            if ($request->expectsJson()) {
                return $this->errorResponse(
                    'شما دسترسی لازم برای انجام این عمل را ندارید',
                    'UNAUTHORIZED',
                    403
                );
            }
            
            return $this->webErrorResponse('شما دسترسی لازم برای انجام این عمل را ندارید');
        }

        if ($e instanceof NotFoundHttpException) {
            if ($request->expectsJson()) {
                return $this->errorResponse(
                    'صفحه مورد نظر یافت نشد',
                    'NOT_FOUND',
                    404
                );
            }
            
            return $this->webErrorResponse('صفحه مورد نظر یافت نشد');
        }

        // Handle generic exceptions
        if ($request->expectsJson()) {
            return $this->errorResponse(
                'خطای داخلی سرور رخ داد. لطفاً بعداً تلاش کنید.',
                'INTERNAL_ERROR',
                500,
                ['error_id' => $errorId]
            );
        }
        
        return $this->webErrorResponse('خطای داخلی سرور رخ داد. لطفاً بعداً تلاش کنید.');
    }

    /**
     * Execute a callback with proper exception handling
     */
    protected function executeWithErrorHandling(callable $callback, Request $request, $context = [])
    {
        try {
            return $callback();
        } catch (Exception $e) {
            return $this->handleException($e, $request, $context);
        }
    }

    /**
     * Validate request data with custom error messages
     */
    protected function validateRequest(Request $request, array $rules, array $messages = []): array
    {
        try {
            return $request->validate($rules, $messages);
        } catch (ValidationException $e) {
            throw $e;
        }
    }

    /**
     * Log user actions for audit trail
     */
    protected function logUserAction(string $action, array $data = [], Request $request = null): void
    {
        $userId = $request && $request->user() ? $request->user()->id : null;
        
        Log::info('User Action', [
            'user_id' => $userId,
            'action' => $action,
            'data' => $data,
            'ip' => $request ? $request->ip() : null,
            'user_agent' => $request ? $request->userAgent() : null,
            'timestamp' => now()->toISOString()
        ]);
    }

    /**
     * Get pagination metadata
     */
    protected function getPaginationMeta($paginator): array
    {
        return [
            'current_page' => $paginator->currentPage(),
            'last_page' => $paginator->lastPage(),
            'per_page' => $paginator->perPage(),
            'total' => $paginator->total(),
            'from' => $paginator->firstItem(),
            'to' => $paginator->lastItem(),
            'has_more' => $paginator->hasMorePages()
        ];
    }

    /**
     * Format validation errors for API responses
     */
    protected function formatValidationErrors(array $errors): array
    {
        $formatted = [];
        
        foreach ($errors as $field => $messages) {
            $formatted[$field] = is_array($messages) ? $messages[0] : $messages;
        }
        
        return $formatted;
    }
}
