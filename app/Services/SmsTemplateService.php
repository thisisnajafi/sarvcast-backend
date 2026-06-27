<?php

namespace App\Services;

use App\Models\SmsTemplate;
use App\Models\User;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class SmsTemplateService
{
    public function __construct(
        private readonly SmsParameterResolver $parameterResolver,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data, ?User $actor = null): SmsTemplate
    {
        $validated = $this->validateTemplateData($data);

        return SmsTemplate::create([
            ...$validated,
            'slug' => $this->generateUniqueSlug($validated['name'], $validated['melipayamak_body_id']),
            'created_by' => $actor?->id,
            'updated_by' => $actor?->id,
        ]);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(SmsTemplate $template, array $data, ?User $actor = null): SmsTemplate
    {
        $merged = array_merge([
            'name' => $template->name,
            'melipayamak_body_id' => $template->melipayamak_body_id,
            'preview_text' => $template->preview_text,
            'parameters' => $template->parameters,
            'category' => $template->category,
            'description' => $template->description,
            'is_active' => $template->is_active,
        ], $data);

        $validated = $this->validateTemplateData($merged, $template);

        $template->update([
            ...$validated,
            'updated_by' => $actor?->id,
        ]);

        return $template->fresh();
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public function validateTemplateData(array $data, ?SmsTemplate $existing = null): array
    {
        $parameters = $data['parameters'] ?? [];
        $previewText = (string) ($data['preview_text'] ?? $existing?->preview_text ?? '');

        $this->assertParametersValid($parameters, $previewText);

        $validated = [
            'name' => $data['name'] ?? $existing?->name,
            'melipayamak_body_id' => (int) ($data['melipayamak_body_id'] ?? $existing?->melipayamak_body_id),
            'preview_text' => $previewText,
            'parameters' => $parameters,
            'category' => $data['category'] ?? $existing?->category ?? SmsTemplate::CATEGORY_MARKETING,
            'description' => $data['description'] ?? $existing?->description,
            'is_active' => array_key_exists('is_active', $data)
                ? (bool) $data['is_active']
                : ($existing?->is_active ?? true),
        ];

        if (! in_array($validated['category'], [
            SmsTemplate::CATEGORY_MARKETING,
            SmsTemplate::CATEGORY_TRANSACTIONAL,
            SmsTemplate::CATEGORY_SYSTEM,
        ], true)) {
            throw ValidationException::withMessages([
                'category' => ['دسته‌بندی قالب نامعتبر است.'],
            ]);
        }

        if ($validated['melipayamak_body_id'] <= 0) {
            throw ValidationException::withMessages([
                'melipayamak_body_id' => ['کد الگوی ملی‌پیامک باید عدد مثبت باشد.'],
            ]);
        }

        return $validated;
    }

    /**
     * @param  array<int, array<string, mixed>>  $parameters
     */
    public function assertParametersValid(array $parameters, string $previewText): void
    {
        if (count($parameters) === 0) {
            throw ValidationException::withMessages([
                'parameters' => ['حداقل یک پارامتر برای قالب الزامی است.'],
            ]);
        }

        $indices = [];
        foreach ($parameters as $param) {
            if (! isset($param['index'], $param['label'], $param['source'])) {
                throw ValidationException::withMessages([
                    'parameters' => ['هر پارامتر باید index، label و source داشته باشد.'],
                ]);
            }

            $index = (int) $param['index'];
            if ($index < 0) {
                throw ValidationException::withMessages([
                    'parameters' => ['شماره پارامتر نمی‌تواند منفی باشد.'],
                ]);
            }

            $indices[] = $index;
        }

        sort($indices);
        $expectedIndices = range(0, count($indices) - 1);
        if ($indices !== $expectedIndices) {
            throw ValidationException::withMessages([
                'parameters' => ['شماره پارامترها باید از 0 به‌صورت پیوسته باشد.'],
            ]);
        }

        preg_match_all('/\{(\d+)\}/', $previewText, $matches);
        $placeholderCount = count(array_unique($matches[1] ?? []));

        if ($placeholderCount > 0 && $placeholderCount !== count($parameters)) {
            throw ValidationException::withMessages([
                'preview_text' => ['تعداد placeholderهای متن پیش‌نمایش با تعداد پارامترها مطابقت ندارد.'],
            ]);
        }
    }

    /**
     * @param  array<int|string, string>  $overrides
     */
    public function buildPreview(SmsTemplate $template, ?User $user = null, array $overrides = []): string
    {
        $values = $this->parameterResolver->resolve(
            $user,
            $template->parameters ?? [],
            $overrides
        );

        return $this->parameterResolver->renderPreview($template->preview_text, $values);
    }

    private function generateUniqueSlug(string $name, int $bodyId): string
    {
        $base = Str::slug($name);
        if ($base === '') {
            $base = 'sms-template';
        }

        $slug = $base.'-'.$bodyId;
        $counter = 1;

        while (SmsTemplate::where('slug', $slug)->exists()) {
            $slug = $base.'-'.$bodyId.'-'.$counter;
            $counter++;
        }

        return $slug;
    }
}
