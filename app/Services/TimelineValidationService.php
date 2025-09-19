<?php

namespace App\Services;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

class TimelineValidationService
{
    /**
     * Timeline validation rules configuration
     */
    private const RULES = [
        'min_duration_per_image' => 2, // seconds
        'max_duration_per_image' => 60, // seconds
        'min_coverage_percentage' => 50, // percentage
        'max_unique_images' => 20,
        'max_gap_between_images' => 30, // seconds
        'max_timeline_entries' => 100,
        'min_timeline_entries' => 1,
        'max_episode_duration' => 7200, // 2 hours in seconds
        'min_episode_duration' => 1, // seconds
    ];

    /**
     * Supported image formats
     */
    private const SUPPORTED_FORMATS = ['jpg', 'jpeg', 'png', 'webp', 'gif'];

    /**
     * Trusted domains for image hosting
     */
    private const TRUSTED_DOMAINS = [
        'cdn.sarvcast.com',
        'images.sarvcast.com',
        'storage.googleapis.com',
        'amazonaws.com',
        'cloudinary.com',
        'imgur.com',
        'unsplash.com'
    ];

    /**
     * Validate complete timeline data
     */
    public function validateTimeline(array $timelineData, int $episodeDuration): array
    {
        $errors = [];
        $warnings = [];

        // Basic validation
        $basicErrors = $this->validateBasicRules($timelineData, $episodeDuration);
        $errors = array_merge($errors, $basicErrors);

        if (empty($errors)) {
            // Advanced validation
            $advancedErrors = $this->validateAdvancedRules($timelineData, $episodeDuration);
            $errors = array_merge($errors, $advancedErrors);

            // Business logic validation
            $businessErrors = $this->validateBusinessLogic($timelineData, $episodeDuration);
            $errors = array_merge($errors, $businessErrors);

            // Performance validation
            $performanceWarnings = $this->validatePerformance($timelineData, $episodeDuration);
            $warnings = array_merge($warnings, $performanceWarnings);
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors,
            'warnings' => $warnings,
            'statistics' => $this->generateStatistics($timelineData, $episodeDuration)
        ];
    }

    /**
     * Validate basic rules
     */
    private function validateBasicRules(array $timelineData, int $episodeDuration): array
    {
        $errors = [];

        // Check episode duration
        if ($episodeDuration < self::RULES['min_episode_duration']) {
            $errors[] = 'مدت زمان اپیزود باید حداقل 1 ثانیه باشد';
        }

        if ($episodeDuration > self::RULES['max_episode_duration']) {
            $errors[] = 'مدت زمان اپیزود نمی‌تواند بیش از 2 ساعت باشد';
        }

        // Check timeline data structure
        if (empty($timelineData)) {
            $errors[] = 'تایم‌لاین نمی‌تواند خالی باشد';
            return $errors;
        }

        if (count($timelineData) > self::RULES['max_timeline_entries']) {
            $errors[] = 'تعداد ورودی‌های تایم‌لاین نمی‌تواند از 100 بیشتر باشد';
        }

        if (count($timelineData) < self::RULES['min_timeline_entries']) {
            $errors[] = 'تایم‌لاین باید حداقل 1 ورودی داشته باشد';
        }

        return $errors;
    }

    /**
     * Validate advanced rules
     */
    private function validateAdvancedRules(array $timelineData, int $episodeDuration): array
    {
        $errors = [];
        $sortedTimeline = collect($timelineData)->sortBy('start_time')->values();

        foreach ($sortedTimeline as $index => $entry) {
            $entryErrors = $this->validateTimelineEntry($entry, $index, $episodeDuration);
            $errors = array_merge($errors, $entryErrors);
        }

        // Validate timeline structure
        $structureErrors = $this->validateTimelineStructure($sortedTimeline, $episodeDuration);
        $errors = array_merge($errors, $structureErrors);

        return $errors;
    }

    /**
     * Validate individual timeline entry
     */
    private function validateTimelineEntry(array $entry, int $index, int $episodeDuration): array
    {
        $errors = [];

        // Check required fields
        if (!isset($entry['start_time']) || !isset($entry['end_time']) || !isset($entry['image_url'])) {
            $errors[] = "ورودی {$index}: فیلدهای start_time، end_time و image_url الزامی هستند";
            return $errors;
        }

        $startTime = $entry['start_time'];
        $endTime = $entry['end_time'];
        $imageUrl = $entry['image_url'];

        // Validate time values
        if (!is_numeric($startTime) || $startTime < 0) {
            $errors[] = "ورودی {$index}: زمان شروع نمی‌تواند منفی باشد";
        }

        if (!is_numeric($endTime) || $endTime > $episodeDuration) {
            $errors[] = "ورودی {$index}: زمان پایان نمی‌تواند بیش از مدت اپیزود باشد";
        }

        if ($startTime >= $endTime) {
            $errors[] = "ورودی {$index}: زمان شروع باید کمتر از زمان پایان باشد";
        }

        // Validate duration
        $duration = $endTime - $startTime;
        if ($duration < self::RULES['min_duration_per_image']) {
            $errors[] = "ورودی {$index}: مدت زمان هر تصویر باید حداقل " . self::RULES['min_duration_per_image'] . " ثانیه باشد";
        }

        if ($duration > self::RULES['max_duration_per_image']) {
            $errors[] = "ورودی {$index}: مدت زمان هر تصویر نمی‌تواند بیش از " . self::RULES['max_duration_per_image'] . " ثانیه باشد";
        }

        // Validate image URL
        $imageErrors = $this->validateImageUrl($imageUrl, $index);
        $errors = array_merge($errors, $imageErrors);

        return $errors;
    }

    /**
     * Validate image URL
     */
    private function validateImageUrl(string $imageUrl, int $index): array
    {
        $errors = [];

        // Check URL format
        if (!filter_var($imageUrl, FILTER_VALIDATE_URL)) {
            $errors[] = "ورودی {$index}: آدرس تصویر نامعتبر است";
            return $errors;
        }

        // Check image format
        $extension = strtolower(pathinfo(parse_url($imageUrl, PHP_URL_PATH), PATHINFO_EXTENSION));
        if (!in_array($extension, self::SUPPORTED_FORMATS)) {
            $errors[] = "ورودی {$index}: فرمت تصویر پشتیبانی نمی‌شود. فرمت‌های مجاز: " . implode(', ', self::SUPPORTED_FORMATS);
        }

        // Check trusted domain
        $host = parse_url($imageUrl, PHP_URL_HOST);
        $isTrustedDomain = false;
        
        foreach (self::TRUSTED_DOMAINS as $domain) {
            if (str_contains($host, $domain)) {
                $isTrustedDomain = true;
                break;
            }
        }

        if (!$isTrustedDomain) {
            $errors[] = "ورودی {$index}: تصویر باید از دامنه‌های معتبر آپلود شود";
        }

        return $errors;
    }

    /**
     * Validate timeline structure
     */
    private function validateTimelineStructure(Collection $sortedTimeline, int $episodeDuration): array
    {
        $errors = [];

        if ($sortedTimeline->isEmpty()) {
            return $errors;
        }

        // Check for overlaps
        for ($i = 0; $i < $sortedTimeline->count() - 1; $i++) {
            $current = $sortedTimeline[$i];
            $next = $sortedTimeline[$i + 1];
            
            if ($current['end_time'] > $next['start_time']) {
                $errors[] = "تداخل زمانی بین ورودی {$i} و " . ($i + 1);
            }
        }

        // Check timeline coverage
        $first = $sortedTimeline->first();
        $last = $sortedTimeline->last();

        if ($first['start_time'] > 0) {
            $errors[] = 'تایم‌لاین باید از ثانیه 0 شروع شود';
        }

        if ($last['end_time'] < $episodeDuration) {
            $errors[] = 'تایم‌لاین باید کل مدت اپیزود را پوشش دهد';
        }

        return $errors;
    }

    /**
     * Validate business logic
     */
    private function validateBusinessLogic(array $timelineData, int $episodeDuration): array
    {
        $errors = [];
        $sortedTimeline = collect($timelineData)->sortBy('start_time')->values();

        $uniqueImages = [];
        $totalDuration = 0;

        foreach ($sortedTimeline as $entry) {
            $duration = $entry['end_time'] - $entry['start_time'];
            $uniqueImages[$entry['image_url']] = true;
            $totalDuration += $duration;
        }

        // Check coverage percentage
        $coveragePercentage = ($totalDuration / $episodeDuration) * 100;
        if ($coveragePercentage < self::RULES['min_coverage_percentage']) {
            $errors[] = "تایم‌لاین باید حداقل " . self::RULES['min_coverage_percentage'] . "% از مدت اپیزود را پوشش دهد. پوشش فعلی: " . round($coveragePercentage, 1) . "%";
        }

        // Check unique image count
        if (count($uniqueImages) > self::RULES['max_unique_images']) {
            $errors[] = "تعداد تصاویر منحصر به فرد نمی‌تواند از " . self::RULES['max_unique_images'] . " بیشتر باشد";
        }

        // Check for reasonable gaps
        for ($i = 1; $i < $sortedTimeline->count(); $i++) {
            $gap = $sortedTimeline[$i]['start_time'] - $sortedTimeline[$i-1]['end_time'];
            
            if ($gap > self::RULES['max_gap_between_images']) {
                $errors[] = "شکاف بزرگ بین ورودی " . ($i-1) . " و {$i}: {$gap} ثانیه";
            }
        }

        return $errors;
    }

    /**
     * Validate performance considerations
     */
    private function validatePerformance(array $timelineData, int $episodeDuration): array
    {
        $warnings = [];
        $sortedTimeline = collect($timelineData)->sortBy('start_time')->values();

        // Check for too many small segments
        $smallSegments = 0;
        foreach ($sortedTimeline as $entry) {
            $duration = $entry['end_time'] - $entry['start_time'];
            if ($duration < 5) {
                $smallSegments++;
            }
        }

        if ($smallSegments > count($timelineData) * 0.5) {
            $warnings[] = "تعداد زیادی از تصاویر مدت زمان کوتاهی دارند که ممکن است بر عملکرد تأثیر بگذارد";
        }

        // Check for too many unique images
        $uniqueImages = array_unique(array_column($timelineData, 'image_url'));
        if (count($uniqueImages) > 15) {
            $warnings[] = "تعداد زیاد تصاویر منحصر به فرد ممکن است زمان بارگذاری را افزایش دهد";
        }

        return $warnings;
    }

    /**
     * Generate timeline statistics
     */
    private function generateStatistics(array $timelineData, int $episodeDuration): array
    {
        $sortedTimeline = collect($timelineData)->sortBy('start_time')->values();
        
        $uniqueImages = array_unique(array_column($timelineData, 'image_url'));
        $totalDuration = 0;
        $durations = [];

        foreach ($timelineData as $entry) {
            $duration = $entry['end_time'] - $entry['start_time'];
            $totalDuration += $duration;
            $durations[] = $duration;
        }

        $coveragePercentage = ($totalDuration / $episodeDuration) * 100;
        $averageDuration = $totalDuration / count($timelineData);
        $minDuration = min($durations);
        $maxDuration = max($durations);

        return [
            'total_entries' => count($timelineData),
            'unique_images' => count($uniqueImages),
            'total_duration' => $totalDuration,
            'coverage_percentage' => round($coveragePercentage, 2),
            'average_duration' => round($averageDuration, 2),
            'min_duration' => $minDuration,
            'max_duration' => $maxDuration,
            'first_image_start' => $sortedTimeline->first()['start_time'] ?? 0,
            'last_image_end' => $sortedTimeline->last()['end_time'] ?? 0,
        ];
    }

    /**
     * Get validation rules configuration
     */
    public function getRules(): array
    {
        return self::RULES;
    }

    /**
     * Get supported image formats
     */
    public function getSupportedFormats(): array
    {
        return self::SUPPORTED_FORMATS;
    }

    /**
     * Get trusted domains
     */
    public function getTrustedDomains(): array
    {
        return self::TRUSTED_DOMAINS;
    }
}
