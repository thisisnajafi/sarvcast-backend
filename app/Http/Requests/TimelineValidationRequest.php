<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class TimelineValidationRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'episode_duration' => 'required|integer|min:1|max:7200', // Max 2 hours
            'image_timeline' => 'required|array|min:1',
            'image_timeline.*.start_time' => 'required|integer|min:0',
            'image_timeline.*.end_time' => 'required|integer|min:0',
            'image_timeline.*.image_url' => 'required|url|max:500',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'episode_duration.required' => 'مدت زمان اپیزود الزامی است',
            'episode_duration.integer' => 'مدت زمان اپیزود باید عدد باشد',
            'episode_duration.min' => 'مدت زمان اپیزود باید حداقل 1 ثانیه باشد',
            'episode_duration.max' => 'مدت زمان اپیزود نمی‌تواند بیش از 2 ساعت باشد',
            
            'image_timeline.required' => 'تایم‌لاین تصاویر الزامی است',
            'image_timeline.array' => 'تایم‌لاین باید آرایه باشد',
            'image_timeline.min' => 'تایم‌لاین باید حداقل 1 ورودی داشته باشد',
            'image_timeline.max' => 'تایم‌لاین نمی‌تواند بیش از 100 ورودی داشته باشد',
            
            'image_timeline.*.start_time.required' => 'زمان شروع الزامی است',
            'image_timeline.*.start_time.integer' => 'زمان شروع باید عدد باشد',
            'image_timeline.*.start_time.min' => 'زمان شروع نمی‌تواند منفی باشد',
            
            'image_timeline.*.end_time.required' => 'زمان پایان الزامی است',
            'image_timeline.*.end_time.integer' => 'زمان پایان باید عدد باشد',
            'image_timeline.*.end_time.min' => 'زمان پایان نمی‌تواند منفی باشد',
            
            'image_timeline.*.image_url.required' => 'آدرس تصویر الزامی است',
            'image_timeline.*.image_url.url' => 'آدرس تصویر باید معتبر باشد',
            'image_timeline.*.image_url.max' => 'آدرس تصویر نمی‌تواند بیش از 500 کاراکتر باشد',
        ];
    }

    /**
     * Configure the validator instance.
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function ($validator) {
            $this->validateTimelineLogic($validator);
        });
    }

    /**
     * Validate timeline business logic
     */
    private function validateTimelineLogic(Validator $validator): void
    {
        $timelineData = $this->input('image_timeline', []);
        $episodeDuration = $this->input('episode_duration', 0);

        if (empty($timelineData) || $episodeDuration <= 0) {
            return;
        }

        // Sort timeline by start time
        $sortedTimeline = collect($timelineData)->sortBy('start_time')->values();

        // Validate each timeline entry
        foreach ($sortedTimeline as $index => $entry) {
            $this->validateTimelineEntry($validator, $entry, $index, $episodeDuration);
        }

        // Validate timeline as a whole
        $this->validateTimelineStructure($validator, $sortedTimeline, $episodeDuration);
    }

    /**
     * Validate individual timeline entry
     */
    private function validateTimelineEntry(Validator $validator, array $entry, int $index, int $episodeDuration): void
    {
        $startTime = $entry['start_time'] ?? 0;
        $endTime = $entry['end_time'] ?? 0;
        $duration = $endTime - $startTime;

        // Removed: start_time must be less than end_time validation

        // Validate duration limits
        if ($duration < 2) {
            $validator->errors()->add(
                "image_timeline.{$index}.end_time",
                "مدت زمان هر تصویر باید حداقل 2 ثانیه باشد"
            );
        }

        if ($duration > 60) {
            $validator->errors()->add(
                "image_timeline.{$index}.end_time",
                "مدت زمان هر تصویر نمی‌تواند بیش از 60 ثانیه باشد"
            );
        }

        // Validate against episode duration
        if ($endTime > $episodeDuration) {
            $validator->errors()->add(
                "image_timeline.{$index}.end_time",
                "زمان پایان نمی‌تواند بیش از مدت اپیزود باشد"
            );
        }

        // Validate image URL format
        $imageUrl = $entry['image_url'] ?? '';
        if (!empty($imageUrl)) {
            $this->validateImageUrl($validator, $imageUrl, $index);
        }
    }

    /**
     * Validate image URL
     */
    private function validateImageUrl(Validator $validator, string $imageUrl, int $index): void
    {
        // Check for supported image formats
        $supportedFormats = ['jpg', 'jpeg', 'png', 'webp', 'gif'];
        $extension = strtolower(pathinfo(parse_url($imageUrl, PHP_URL_PATH), PATHINFO_EXTENSION));
        
        if (!in_array($extension, $supportedFormats)) {
            $validator->errors()->add(
                "image_timeline.{$index}.image_url",
                "فرمت تصویر پشتیبانی نمی‌شود. فرمت‌های مجاز: " . implode(', ', $supportedFormats)
            );
        }

        // Check for CDN or trusted domains
        $trustedDomains = [
            'cdn.sarvcast.com',
            'images.sarvcast.com',
            'storage.googleapis.com',
            'amazonaws.com',
            'cloudinary.com'
        ];

        $host = parse_url($imageUrl, PHP_URL_HOST);
        $isTrustedDomain = false;
        
        foreach ($trustedDomains as $domain) {
            if (str_contains($host, $domain)) {
                $isTrustedDomain = true;
                break;
            }
        }

        if (!$isTrustedDomain) {
            $validator->errors()->add(
                "image_timeline.{$index}.image_url",
                "تصویر باید از دامنه‌های معتبر آپلود شود"
            );
        }
    }

    /**
     * Validate timeline structure
     */
    private function validateTimelineStructure(Validator $validator, $sortedTimeline, int $episodeDuration): void
    {
        if ($sortedTimeline->isEmpty()) {
            return;
        }

        $uniqueImages = [];
        $totalDuration = 0;
        $overlaps = [];

        // Check for overlaps and collect statistics
        for ($i = 0; $i < $sortedTimeline->count(); $i++) {
            $current = $sortedTimeline[$i];
            $duration = $current['end_time'] - $current['start_time'];
            
            $uniqueImages[$current['image_url']] = true;
            $totalDuration += $duration;

            // Check for overlaps with next entry
            if ($i < $sortedTimeline->count() - 1) {
                $next = $sortedTimeline[$i + 1];
                
                if ($current['end_time'] > $next['start_time']) {
                    $overlaps[] = "تداخل بین ورودی {$i} و " . ($i + 1);
                }
            }
        }

        // Report overlaps
        if (!empty($overlaps)) {
            $validator->errors()->add(
                'image_timeline',
                'تداخل زمانی: ' . implode(', ', $overlaps)
            );
        }

        // Validate coverage
        $coveragePercentage = ($totalDuration / $episodeDuration) * 100;
        if ($coveragePercentage < 50) {
            $validator->errors()->add(
                'image_timeline',
                "تایم‌لاین باید حداقل 50% از مدت اپیزود را پوشش دهد. پوشش فعلی: " . round($coveragePercentage, 1) . "%"
            );
        }

        // Validate unique image count
        if (count($uniqueImages) > 20) {
            $validator->errors()->add(
                'image_timeline',
                'تعداد تصاویر منحصر به فرد نمی‌تواند از 20 بیشتر باشد'
            );
        }

        // Validate timeline starts from beginning
        $first = $sortedTimeline->first();
        if ($first['start_time'] > 0) {
            $validator->errors()->add(
                'image_timeline.0.start_time',
                'تایم‌لاین باید از ثانیه 0 شروع شود'
            );
        }

        // Validate timeline covers full duration
        $last = $sortedTimeline->last();
        if ($last['end_time'] < $episodeDuration) {
            $validator->errors()->add(
                'image_timeline',
                'تایم‌لاین باید کل مدت اپیزود را پوشش دهد'
            );
        }

        // Check for reasonable gaps
        for ($i = 1; $i < $sortedTimeline->count(); $i++) {
            $gap = $sortedTimeline[$i]['start_time'] - $sortedTimeline[$i-1]['end_time'];
            
            if ($gap > 30) {
                $validator->errors()->add(
                    'image_timeline',
                    "شکاف بزرگ بین ورودی " . ($i-1) . " و {$i}: {$gap} ثانیه"
                );
            }
        }
    }

    /**
     * Handle a failed validation attempt.
     */
    protected function failedValidation(Validator $validator): void
    {
        throw new HttpResponseException(
            response()->json([
                'success' => false,
                'message' => 'خطا در اعتبارسنجی تایم‌لاین',
                'errors' => $validator->errors()
            ], 422)
        );
    }
}