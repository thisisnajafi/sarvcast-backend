<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class OldStoriesRemoteManageClient
{
    public function verifyAccess(): array
    {
        return app(OldStoriesRemoteImportClient::class)->verifyAccess();
    }

    /**
     * @param  array<string, mixed>  $params
     * @return array<string, mixed>
     */
    public function deleteStory(array $params): array
    {
        return $this->postJson('/local-import/stories/manage/delete-story', $params);
    }

    /**
     * @param  array<string, mixed>  $params
     * @return array<string, mixed>
     */
    public function deleteEpisode(array $params): array
    {
        return $this->postJson('/local-import/stories/manage/delete-episode', $params);
    }

    /**
     * @param  array<string, mixed>  $params
     * @return array<string, mixed>
     */
    public function deleteScript(array $params): array
    {
        return $this->postJson('/local-import/stories/manage/delete-script', $params);
    }

    /**
     * @param  array<string, mixed>  $params
     * @return array<string, mixed>
     */
    public function deleteCharacter(array $params): array
    {
        return $this->postJson('/local-import/stories/manage/delete-character', $params);
    }

    /**
     * @param  array<string, mixed>  $params
     * @return array<string, mixed>
     */
    public function updateCharacters(string $filePath, array $params): array
    {
        return $this->postFile('/local-import/stories/manage/update-characters', 'file', $filePath, $params);
    }

    /**
     * @param  array<string, mixed>  $params
     * @return array<string, mixed>
     */
    public function updateScript(string $filePath, array $params): array
    {
        return $this->postFile('/local-import/stories/manage/update-script', 'file', $filePath, $params);
    }

    /**
     * @param  array<string, mixed>  $params
     * @return array<string, mixed>
     */
    public function updatePrompts(string $filePath, array $params): array
    {
        return $this->postFile('/local-import/stories/manage/update-prompts', 'file', $filePath, $params);
    }

    /**
     * @param  array<string, mixed>  $params
     * @return array<string, mixed>
     */
    private function postJson(string $path, array $params): array
    {
        [$baseUrl, $token] = $this->credentials();

        $response = Http::withToken($token)
            ->timeout(120)
            ->post("{$baseUrl}{$path}", $params);

        return $this->parseResponse($response, 'Remote manage request failed');
    }

    /**
     * @param  array<string, mixed>  $params
     * @return array<string, mixed>
     */
    private function postFile(string $path, string $field, string $filePath, array $params): array
    {
        if (! is_file($filePath)) {
            throw new \RuntimeException("File not found: {$filePath}");
        }

        [$baseUrl, $token] = $this->credentials();

        $response = Http::withToken($token)
            ->timeout(300)
            ->attach($field, fopen($filePath, 'r'), basename($filePath))
            ->post("{$baseUrl}{$path}", $params);

        return $this->parseResponse($response, 'Remote file update failed');
    }

    /**
     * @return array{0: string, 1: string}
     */
    private function credentials(): array
    {
        $baseUrl = rtrim((string) env('LOCAL_IMPORT_API_BASE_URL'), '/');
        $token = (string) env('LOCAL_IMPORT_API_TOKEN');

        if ($baseUrl === '' || $token === '') {
            throw new \RuntimeException(
                'Set LOCAL_IMPORT_API_BASE_URL and LOCAL_IMPORT_API_TOKEN in manji-laravel/.env (local machine only).'
            );
        }

        return [$baseUrl, $token];
    }

    /**
     * @return array<string, mixed>
     */
    private function parseResponse(\Illuminate\Http\Client\Response $response, string $label): array
    {
        if (! $response->successful()) {
            $message = $response->json('message') ?? $response->body();
            throw new \RuntimeException("{$label} (HTTP {$response->status()}): {$message}");
        }

        $payload = $response->json();
        if (! is_array($payload) || ! ($payload['success'] ?? false)) {
            throw new \RuntimeException("{$label}: unsuccessful response.");
        }

        return is_array($payload['data'] ?? null) ? $payload['data'] : [];
    }
}
