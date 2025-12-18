<?php

namespace App\Helpers;

use Morilog\Jalali\Jalalian;
use Carbon\Carbon;

class JalaliHelper
{
    /**
     * Convert Carbon date to Jalali format
     */
    public static function toJalali($date, $format = 'Y/m/d')
    {
        if (!$date) {
            return null;
        }

        if ($date instanceof Carbon) {
            return Jalalian::fromCarbon($date)->format($format);
        }

        if (is_string($date)) {
            $carbon = Carbon::parse($date);
            return Jalalian::fromCarbon($carbon)->format($format);
        }

        return null;
    }

    /**
     * Convert Jalali date to Carbon
     */
    public static function toCarbon($jalaliDate, $format = 'Y/m/d')
    {
        if (!$jalaliDate) {
            return null;
        }

        try {
            $jalalian = Jalalian::fromFormat($format, $jalaliDate);
            return $jalalian->toCarbon();
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Format date for display
     */
    public static function formatForDisplay($date, $format = 'Y/m/d H:i')
    {
        if (!$date) {
            return 'تاریخ نامشخص';
        }

        $jalali = self::toJalali($date, $format);
        return $jalali ?: 'تاریخ نامشخص';
    }

    /**
     * Format date for display with Persian month names
     */
    public static function formatWithPersianMonth($date)
    {
        if (!$date) {
            return 'تاریخ نامشخص';
        }

        try {
            $jalalian = Jalalian::fromCarbon($date instanceof Carbon ? $date : Carbon::parse($date));
            
            $persianMonths = [
                1 => 'فروردین', 2 => 'اردیبهشت', 3 => 'خرداد',
                4 => 'تیر', 5 => 'مرداد', 6 => 'شهریور',
                7 => 'مهر', 8 => 'آبان', 9 => 'آذر',
                10 => 'دی', 11 => 'بهمن', 12 => 'اسفند'
            ];

            $day = $jalalian->getDay();
            $month = $persianMonths[$jalalian->getMonth()];
            $year = $jalalian->getYear();

            return "{$day} {$month} {$year}";
        } catch (\Exception $e) {
            return 'تاریخ نامشخص';
        }
    }

    /**
     * Format date for display with Persian month names and time
     */
    public static function formatWithPersianMonthAndTime($date)
    {
        if (!$date) {
            return 'تاریخ نامشخص';
        }

        try {
            $jalalian = Jalalian::fromCarbon($date instanceof Carbon ? $date : Carbon::parse($date));
            
            $persianMonths = [
                1 => 'فروردین', 2 => 'اردیبهشت', 3 => 'خرداد',
                4 => 'تیر', 5 => 'مرداد', 6 => 'شهریور',
                7 => 'مهر', 8 => 'آبان', 9 => 'آذر',
                10 => 'دی', 11 => 'بهمن', 12 => 'اسفند'
            ];

            $day = $jalalian->getDay();
            $month = $persianMonths[$jalalian->getMonth()];
            $year = $jalalian->getYear();
            $time = $jalalian->format('H:i');

            return "{$day} {$month} {$year} - {$time}";
        } catch (\Exception $e) {
            return 'تاریخ نامشخص';
        }
    }

    /**
     * Get relative time in Persian
     */
    public static function getRelativeTime($date)
    {
        if (!$date) {
            return 'تاریخ نامشخص';
        }

        try {
            $carbon = $date instanceof Carbon ? $date : Carbon::parse($date);
            $jalalian = Jalalian::fromCarbon($carbon);
            
            $now = Jalalian::now();
            $diff = $now->getTimestamp() - $jalalian->getTimestamp();

            if ($diff < 60) {
                return 'همین الان';
            } elseif ($diff < 3600) {
                $minutes = floor($diff / 60);
                return "{$minutes} دقیقه پیش";
            } elseif ($diff < 86400) {
                $hours = floor($diff / 3600);
                return "{$hours} ساعت پیش";
            } elseif ($diff < 2592000) {
                $days = floor($diff / 86400);
                return "{$days} روز پیش";
            } elseif ($diff < 31536000) {
                $months = floor($diff / 2592000);
                return "{$months} ماه پیش";
            } else {
                $years = floor($diff / 31536000);
                return "{$years} سال پیش";
            }
        } catch (\Exception $e) {
            return 'تاریخ نامشخص';
        }
    }

    /**
     * Get current Jalali date
     */
    public static function now($format = 'Y/m/d H:i')
    {
        return Jalalian::now()->format($format);
    }

    /**
     * Get current Jalali date with Persian month
     */
    public static function nowWithPersianMonth()
    {
        return self::formatWithPersianMonth(Carbon::now());
    }

    /**
     * Validate Jalali date
     */
    public static function isValidJalaliDate($date, $format = 'Y/m/d')
    {
        try {
            Jalalian::fromFormat($format, $date);
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Get Jalali month name
     */
    public static function getMonthName($monthNumber)
    {
        $persianMonths = [
            1 => 'فروردین', 2 => 'اردیبهشت', 3 => 'خرداد',
            4 => 'تیر', 5 => 'مرداد', 6 => 'شهریور',
            7 => 'مهر', 8 => 'آبان', 9 => 'آذر',
            10 => 'دی', 11 => 'بهمن', 12 => 'اسفند'
        ];

        return $persianMonths[$monthNumber] ?? 'نامشخص';
    }

    /**
     * Get Jalali day name
     */
    public static function getDayName($dayNumber)
    {
        $persianDays = [
            1 => 'شنبه', 2 => 'یکشنبه', 3 => 'دوشنبه',
            4 => 'سه‌شنبه', 5 => 'چهارشنبه', 6 => 'پنج‌شنبه', 7 => 'جمعه'
        ];

        return $persianDays[$dayNumber] ?? 'نامشخص';
    }
}
