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

    protected function prepareForValidation(): void
    {
        $payload = $this->all();

        if (isset($payload['metadata']) && is_array($payload['metadata'])) {
            $payload['metadata']['title_persian'] = trim((string) ($payload['metadata']['title_persian'] ?? ''));
            $payload['metadata']['episode_number'] = (int) ($payload['metadata']['episode_number'] ?? 0);
            $payload['metadata']['total_episodes'] = (int) ($payload['metadata']['total_episodes'] ?? 0);
            $payload['metadata']['age_range'] = trim((string) ($payload['metadata']['age_range'] ?? ''));
            $payload['metadata']['duration_estimate'] = trim((string) ($payload['metadata']['duration_estimate'] ?? ''));
            $payload['metadata']['main_message'] = trim((string) ($payload['metadata']['main_message'] ?? ''));
            $payload['metadata']['genre_tags'] = array_values(array_filter(array_map(
                static fn ($tag) => trim((string) $tag),
                (array) ($payload['metadata']['genre_tags'] ?? []),
            ), static fn (string $tag) => $tag !== ''));
        }

        if (isset($payload['characters']) && is_array($payload['characters'])) {
            $payload['characters'] = array_values(array_filter(
                $payload['characters'],
                static function ($character): bool {
                    if (! is_array($character)) {
                        return false;
                    }

                    return trim((string) ($character['name_persian'] ?? '')) !== ''
                        || trim((string) ($character['character_id'] ?? '')) !== '';
                }
            ));

            foreach ($payload['characters'] as $index => $character) {
                $payload['characters'][$index]['name_persian'] = trim((string) ($character['name_persian'] ?? ''));
                $payload['characters'][$index]['character_id'] = trim((string) ($character['character_id'] ?? ''));
                $payload['characters'][$index]['description'] = trim((string) ($character['description'] ?? ''));
            }
        }

        if (isset($payload['scenes']) && is_array($payload['scenes'])) {
            $scenes = [];

            foreach ($payload['scenes'] as $scene) {
                if (! is_array($scene)) {
                    continue;
                }

                $dialogueLines = [];
                foreach ((array) ($scene['dialogue_lines'] ?? []) as $line) {
                    if (! is_array($line)) {
                        continue;
                    }

                    $speaker = trim((string) ($line['speaker'] ?? ''));
                    $text = trim((string) ($line['text'] ?? ''));
                    if ($speaker === '' || $text === '') {
                        continue;
                    }

                    $emotion = $line['emotion_tag'] ?? null;
                    $dialogueLines[] = [
                        'speaker' => $speaker,
                        'emotion_tag' => $emotion === null || trim((string) $emotion) === ''
                            ? null
                            : trim((string) $emotion),
                        'text' => $text,
                    ];
                }

                $title = trim((string) ($scene['title'] ?? ''));
                if ($title === '' && $dialogueLines === []) {
                    continue;
                }

                $scenes[] = [
                    'title' => $title,
                    'environment_description' => trim((string) ($scene['environment_description'] ?? '')),
                    'dialogue_lines' => $dialogueLines,
                ];
            }

            $payload['scenes'] = $scenes;
        }

        if (isset($payload['closing']) && is_array($payload['closing'])) {
            $payload['closing']['episode_summary'] = trim((string) ($payload['closing']['episode_summary'] ?? ''));
            $payload['closing']['educational_message'] = trim((string) ($payload['closing']['educational_message'] ?? ''));
            $payload['closing']['soft_hook_text'] = trim((string) ($payload['closing']['soft_hook_text'] ?? ''));
            $payload['closing']['is_final_episode'] = filter_var(
                $payload['closing']['is_final_episode'] ?? false,
                FILTER_VALIDATE_BOOLEAN,
                FILTER_NULL_ON_FAILURE
            ) ?? false;
        }

        unset($payload['characters_raw_unparsed'], $payload['scenes_raw_unparsed']);

        $this->replace($payload);
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
            'metadata.genre_tags' => ['present', 'array'],
            'metadata.genre_tags.*' => ['required', 'string', 'max:200'],
            'metadata.main_message' => ['required', 'string', 'max:2000'],

            'characters' => ['required', 'array'],
            'characters.*.name_persian' => ['required', 'string', 'max:200'],
            'characters.*.character_id' => ['required', 'string', 'max:200'],
            'characters.*.description' => ['nullable', 'string', 'max:5000'],

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
            'scenes.*.title.required' => 'عنوان هر صحنه الزامی است.',
            'scenes.*.dialogue_lines.min' => 'هر صحنه باید حداقل یک خط گفتگو با متن داشته باشد.',
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
