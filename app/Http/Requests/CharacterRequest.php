<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use App\Models\User;

class CharacterRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // Authorization handled by middleware
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        // Merge story_id from route parameter if not present in request
        $storyIdFromRoute = $this->route('storyId') ?? $this->route('story');
        if ($storyIdFromRoute && !$this->has('story_id')) {
            $this->merge([
                'story_id' => $storyIdFromRoute,
            ]);
        }
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $characterId = $this->route('character') ?? $this->route('characterId');

        return [
            'story_id' => ['required', 'integer', 'exists:stories,id'],
            'name' => ['required', 'string', 'max:200'],
            'image' => ['nullable', 'image', 'mimes:jpeg,jpg,png,webp', 'max:5120'], // 5MB max
            'image_url' => ['nullable', 'string', 'max:500'], // For URL input (alternative to file upload)
            'voice_actor_id' => [
                'nullable',
                'integer',
                'exists:users,id',
                function ($attribute, $value, $fail) {
                    if ($value) {
                        $user = User::find($value);
                        if ($user && !in_array($user->role, [
                            User::ROLE_VOICE_ACTOR,
                            User::ROLE_ADMIN,
                            User::ROLE_SUPER_ADMIN
                        ])) {
                            $fail('کاربر انتخاب شده باید نقش صداپیشه، ادمین یا ادمین کل داشته باشد.');
                        }
                    }
                },
            ],
            'description' => ['nullable', 'string', 'max:1000'],
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'story_id.required' => 'شناسه داستان الزامی است',
            'story_id.exists' => 'داستان انتخاب شده معتبر نیست',
            'name.required' => 'نام شخصیت الزامی است',
            'name.max' => 'نام شخصیت نمی‌تواند بیشتر از 200 کاراکتر باشد',
            'image.image' => 'فایل باید یک تصویر معتبر باشد',
            'image.mimes' => 'فرمت تصویر باید jpeg، jpg، png یا webp باشد',
            'image.max' => 'حجم تصویر نمی‌تواند بیشتر از 5 مگابایت باشد',
            'image_url.max' => 'آدرس تصویر نمی‌تواند بیشتر از 500 کاراکتر باشد',
            'voice_actor_id.exists' => 'کاربر انتخاب شده معتبر نیست',
            'description.max' => 'توضیحات نمی‌تواند بیشتر از 1000 کاراکتر باشد',
        ];
    }
}
