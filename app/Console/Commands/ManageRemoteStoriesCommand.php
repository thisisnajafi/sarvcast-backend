<?php

namespace App\Console\Commands;

use App\Services\OldStoriesRemoteManageClient;
use Illuminate\Console\Command;

class ManageRemoteStoriesCommand extends Command
{
    protected $signature = 'stories:manage-remote
                            {action : delete|update}
                            {target : story|episode|script|character|characters|prompts}
                            {--story= : Story folder query or numeric id}
                            {--story-id= : Database story id}
                            {--episode= : Episode number}
                            {--episode-id= : Database episode id}
                            {--character-key= : Character key in characters_and_objects.json}
                            {--character-id= : Database character id}
                            {--file= : Local file path for update targets}
                            {--folder-name= : Exact server folder name override}';

    protected $description = 'Delete or update stories/episodes/scripts/characters on remote server via local-import API';

    public function __construct(
        private readonly OldStoriesRemoteManageClient $client,
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $action = strtolower((string) $this->argument('action'));
        $target = strtolower((string) $this->argument('target'));

        if (! in_array($action, ['delete', 'update'], true)) {
            $this->error('action must be delete or update');

            return self::FAILURE;
        }

        $storyRef = $this->storyReferenceParams();

        try {
            $result = match ([$action, $target]) {
                ['delete', 'story'] => $this->client->deleteStory($storyRef),
                ['delete', 'episode'] => $this->client->deleteEpisode($this->episodeReferenceParams($storyRef)),
                ['delete', 'script'] => $this->client->deleteScript($this->episodeReferenceParams($storyRef)),
                ['delete', 'character'] => $this->client->deleteCharacter(array_merge($storyRef, array_filter([
                    'character_id' => $this->option('character-id'),
                    'character_key' => $this->option('character-key'),
                ], fn ($v) => $v !== null && $v !== ''))),
                ['update', 'characters'] => $this->client->updateCharacters(
                    (string) $this->requireFile(),
                    $storyRef,
                ),
                ['update', 'script'] => $this->client->updateScript(
                    (string) $this->requireFile(),
                    $this->episodeReferenceParams($storyRef),
                ),
                ['update', 'prompts'] => $this->client->updatePrompts(
                    (string) $this->requireFile(),
                    $this->episodeReferenceParams($storyRef),
                ),
                default => throw new \InvalidArgumentException("Unsupported combination: {$action} {$target}"),
            };
        } catch (\Throwable $e) {
            $this->error($e->getMessage());

            return self::FAILURE;
        }

        $this->line(json_encode($result, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));

        return self::SUCCESS;
    }

    /**
     * @return array<string, mixed>
     */
    private function storyReferenceParams(): array
    {
        $params = array_filter([
            'story_id' => $this->option('story-id'),
            'folder_name' => $this->option('folder-name') ?: $this->option('story'),
        ], fn ($v) => $v !== null && $v !== '');

        if ($params === []) {
            throw new \InvalidArgumentException('Provide --story or --story-id or --folder-name');
        }

        return $params;
    }

    /**
     * @param  array<string, mixed>  $storyRef
     * @return array<string, mixed>
     */
    private function episodeReferenceParams(array $storyRef): array
    {
        $episodeRef = array_filter([
            'episode_id' => $this->option('episode-id'),
            'episode_number' => $this->option('episode'),
        ], fn ($v) => $v !== null && $v !== '');

        if ($episodeRef === []) {
            throw new \InvalidArgumentException('Provide --episode or --episode-id');
        }

        return array_merge($storyRef, $episodeRef);
    }

    private function requireFile(): string
    {
        $file = (string) ($this->option('file') ?? '');
        if ($file === '' || ! is_file($file)) {
            throw new \InvalidArgumentException('Provide valid --file= path for update target');
        }

        return $file;
    }
}
