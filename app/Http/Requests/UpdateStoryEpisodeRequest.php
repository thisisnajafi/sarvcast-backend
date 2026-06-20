<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class UpdateStoryEpisodeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'metadata' => ['required', 'array'],
            'metadata.title_persian' => ['required', 'string', 'max:500'],
            'metadata.episode_number' => ['required', 'integer', 'min:1'],
            'metadata.total_episodes' => ['required', 'integer', 'min:1'],
            'metadata.age_range' => ['required', 'string', 'max:100'],
            'metadata.duration_estimate' => ['required', 'string', 'max:100'],
            'metadata.genre_tags' => ['required', 'array', 'min:1'],
            'metadata.genre_tags.*' => ['required', 'string', 'max:200'],
            'metadata.main_message' => ['required', 'string', 'max:2000'],

            'characters' => ['required', 'array'],
            'characters.*.name_persian' => ['required', 'string', 'max:200'],
            'characters.*.character_id' => ['required', 'string', 'max:200'],
            'characters.*.description' => ['required', 'string', 'max:5000'],

            'scenes' => ['required', 'array', 'min:1'],
            'scenes.*.title' => ['required', 'string', 'max:500'],
            'scenes.*.environment_description' => ['nullable', 'string', 'max:5000'],
            'scenes.*.dialogue_lines' => ['required', 'array', 'min:1'],
            'scenes.*.dialogue_lines.*.speaker' => ['required', 'string', 'max:200'],
            'scenes.*.dialogue_lines.*.emotion_tag' => ['nullable', 'string', 'max:200'],
            'scenes.*.dialogue_lines.*.text' => ['required', 'string', 'max:10000'],

            'closing' => ['required', 'array'],
            'closing.episode_summary' => ['required', 'string', 'max:5000'],
            'closing.educational_message' => ['required', 'string', 'max:5000'],
            'closing.is_final_episode' => ['required', 'boolean'],
            'closing.soft_hook_text' => ['nullable', 'string', 'max:5000'],
        ];
    }

    public function messages(): array
    {
        return [
            'metadata.title_persian.required' => 'عنوان فارسی قسمت الزامی است.',
            'scenes.required' => 'حداقل یک صحنه الزامی است.',
            'scenes.min' => 'حداقل یک صحنه الزامی است.',
            'scenes.*.dialogue_lines.min' => 'هر صحنه باید حداقل یک خط گفتگو داشته باشد.',
        ];
    }

    protected function failedValidation(Validator $validator): void
    {
        throw new HttpResponseException(response()->json([
            'success' => false,
            'message' => 'اطلاعات وارد شده معتبر نیست.',
            'errors' => $validator->errors(),
        ], 422));
    }
}
