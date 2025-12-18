<?php

namespace App\Exceptions;

use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\TooManyRequestsHttpException;
use Throwable;

class Handler extends ExceptionHandler
{
    /**
     * A list of exception types with their corresponding custom log levels.
     *
     * @var array<class-string<\Throwable>, \Psr\Log\LogLevel::*>
     */
    protected $levels = [
        //
    ];

    /**
     * A list of the exception types that are not reported.
     *
     * @var array<int, class-string<\Throwable>>
     */
    protected $dontReport = [
        //
    ];

    /**
     * A list of the inputs that are never flashed to the session on validation exceptions.
     *
     * @var array<int, string>
     */
    protected $dontFlash = [
        'current_password',
        'password',
        'password_confirmation',
    ];

    /**
     * Register the exception handling callbacks for the application.
     */
    public function register(): void
    {
        $this->reportable(function (Throwable $e) {
            //
        });
    }

    /**
     * Render an exception into an HTTP response.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Throwable  $e
     * @return \Symfony\Component\HttpFoundation\Response
     *
     * @throws \Throwable
     */
    public function render($request, Throwable $e)
    {
        // Handle specific HTTP exceptions with custom error pages
        if ($e instanceof NotFoundHttpException) {
            return response()->view('errors.404', [], 404);
        }

        if ($e instanceof AccessDeniedHttpException) {
            return response()->view('errors.403', [], 403);
        }

        if ($e instanceof TooManyRequestsHttpException) {
            return response()->view('errors.429', [], 429);
        }

        // Handle CSRF token mismatch (419)
        if ($e instanceof \Illuminate\Session\TokenMismatchException) {
            return response()->view('errors.419', [], 419);
        }

        // Handle server errors (500)
        if ($e instanceof HttpException && $e->getStatusCode() >= 500) {
            return response()->view('errors.500', [], $e->getStatusCode());
        }

        // Handle other HTTP exceptions
        if ($e instanceof HttpException) {
            $statusCode = $e->getStatusCode();
            
            // Check if we have a specific error page for this status code
            $errorView = "errors.{$statusCode}";
            if (view()->exists($errorView)) {
                return response()->view($errorView, [], $statusCode);
            }
            
            // Fallback to generic error page
            return response()->view('errors.error', [
                'error_code' => $statusCode,
                'error_title' => $this->getErrorTitle($statusCode),
                'error_message' => $this->getErrorMessage($statusCode)
            ], $statusCode);
        }

        // For other exceptions, use the default Laravel behavior
        return parent::render($request, $e);
    }

    /**
     * Get error title based on status code
     */
    private function getErrorTitle(int $statusCode): string
    {
        return match ($statusCode) {
            400 => 'درخواست نامعتبر',
            401 => 'عدم احراز هویت',
            402 => 'نیاز به پرداخت',
            404 => 'صفحه یافت نشد',
            403 => 'دسترسی غیرمجاز',
            405 => 'روش غیرمجاز',
            406 => 'غیرقابل قبول',
            408 => 'زمان درخواست تمام شده',
            409 => 'تداخل',
            410 => 'منبع حذف شده',
            411 => 'طول مورد نیاز',
            412 => 'شرط ناموفق',
            413 => 'درخواست خیلی بزرگ',
            414 => 'آدرس خیلی طولانی',
            415 => 'نوع رسانه پشتیبانی نمی‌شود',
            416 => 'محدوده غیرقابل قبول',
            417 => 'انتظار ناموفق',
            418 => 'من یک قوری هستم',
            422 => 'موجودیت غیرقابل پردازش',
            423 => 'قفل شده',
            424 => 'وابستگی ناموفق',
            425 => 'خیلی زود',
            426 => 'ارتقا مورد نیاز',
            428 => 'شرط اولیه مورد نیاز',
            429 => 'درخواست‌های زیاد',
            431 => 'هدرهای درخواست خیلی بزرگ',
            451 => 'غیرقابل دسترس به دلایل قانونی',
            500 => 'خطای داخلی سرور',
            501 => 'پیاده‌سازی نشده',
            502 => 'درگاه نامعتبر',
            503 => 'سرویس در دسترس نیست',
            504 => 'زمان درگاه تمام شده',
            505 => 'نسخه HTTP پشتیبانی نمی‌شود',
            506 => 'تنوع نیز مذاکره می‌کند',
            507 => 'فضای ذخیره‌سازی ناکافی',
            508 => 'حلقه شناسایی شده',
            510 => 'توسعه نیافته',
            511 => 'احراز هویت شبکه مورد نیاز',
            default => 'خطای سیستم'
        };
    }

    /**
     * Get error message based on status code
     */
    private function getErrorMessage(int $statusCode): string
    {
        return match ($statusCode) {
            400 => 'درخواست شما نامعتبر است. لطفاً اطلاعات را بررسی کنید.',
            401 => 'برای دسترسی به این صفحه باید وارد سیستم شوید.',
            402 => 'برای دسترسی به این محتوا نیاز به پرداخت دارید.',
            404 => 'صفحه‌ای که به دنبال آن هستید وجود ندارد.',
            403 => 'شما مجاز به دسترسی به این صفحه نیستید.',
            405 => 'روش درخواست شما برای این صفحه مجاز نیست.',
            406 => 'درخواست شما قابل قبول نیست.',
            408 => 'زمان درخواست شما تمام شده است.',
            409 => 'تداخلی در درخواست شما وجود دارد.',
            410 => 'منبع مورد نظر حذف شده است.',
            411 => 'طول محتوا مورد نیاز است.',
            412 => 'شرط درخواست شما ناموفق است.',
            413 => 'درخواست شما خیلی بزرگ است.',
            414 => 'آدرس درخواست خیلی طولانی است.',
            415 => 'نوع رسانه درخواست پشتیبانی نمی‌شود.',
            416 => 'محدوده درخواست غیرقابل قبول است.',
            417 => 'انتظار درخواست ناموفق است.',
            418 => 'من یک قوری هستم و نمی‌توانم قهوه دم کنم.',
            422 => 'موجودیت درخواست غیرقابل پردازش است.',
            423 => 'منبع قفل شده است.',
            424 => 'وابستگی درخواست ناموفق است.',
            425 => 'درخواست خیلی زود است.',
            426 => 'ارتقا مورد نیاز است.',
            428 => 'شرط اولیه مورد نیاز است.',
            429 => 'تعداد درخواست‌های شما از حد مجاز بیشتر است.',
            431 => 'هدرهای درخواست خیلی بزرگ هستند.',
            451 => 'دسترسی به دلایل قانونی غیرقابل دسترس است.',
            500 => 'خطایی در سرور رخ داده است.',
            501 => 'این قابلیت پیاده‌سازی نشده است.',
            502 => 'درگاه نامعتبر است.',
            503 => 'سرویس در حال حاضر در دسترس نیست.',
            504 => 'زمان درگاه تمام شده است.',
            505 => 'نسخه HTTP پشتیبانی نمی‌شود.',
            506 => 'تنوع نیز مذاکره می‌کند.',
            507 => 'فضای ذخیره‌سازی ناکافی است.',
            508 => 'حلقه شناسایی شده است.',
            510 => 'توسعه نیافته است.',
            511 => 'احراز هویت شبکه مورد نیاز است.',
            default => 'خطایی در سیستم رخ داده است. لطفاً بعداً دوباره تلاش کنید.'
        };
    }
}
