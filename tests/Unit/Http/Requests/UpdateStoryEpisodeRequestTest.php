<?php

namespace Tests\Unit\Http\Requests;

use App\Http\Requests\UpdateStoryEpisodeRequest;
use App\Services\StoryMarkdownService;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class UpdateStoryEpisodeRequestTest extends TestCase
{
    public function test_parsed_fixture_passes_validation(): void
    {
        $fixture = base_path('tests/fixtures/story_markdown/episode_1.md');
        $parsed = (new StoryMarkdownService())->parse((string) file_get_contents($fixture));

        $request = UpdateStoryEpisodeRequest::create(
            '/api/admin/story-editor/stories/test/episodes/episode-1',
            'PUT',
            $parsed,
        );
        $request->setContainer($this->app);
        $this->invokePrepareForValidation($request);

        $validator = Validator::make($request->all(), (new UpdateStoryEpisodeRequest())->rules());

        $this->assertFalse($validator->fails(), json_encode($validator->errors()->toArray(), JSON_UNESCAPED_UNICODE));
    }

    public function test_empty_dialogue_lines_are_stripped_before_validation(): void
    {
        $fixture = base_path('tests/fixtures/story_markdown/episode_4.md');
        $parsed = (new StoryMarkdownService())->parse((string) file_get_contents($fixture));

        $parsed['scenes'][0]['dialogue_lines'][] = [
            'speaker' => 'راوی',
            'emotion_tag' => null,
            'text' => '',
        ];

        $request = UpdateStoryEpisodeRequest::create(
            '/api/admin/story-editor/stories/test/episodes/episode-4',
            'PUT',
            $parsed,
        );
        $request->setContainer($this->app);
        $this->invokePrepareForValidation($request);

        $validator = Validator::make($request->all(), (new UpdateStoryEpisodeRequest())->rules());

        $this->assertFalse($validator->fails(), json_encode($validator->errors()->toArray(), JSON_UNESCAPED_UNICODE));
    }

    public function test_draft_scene_without_title_or_dialogue_is_removed(): void
    {
        $fixture = base_path('tests/fixtures/story_markdown/episode_4.md');
        $parsed = (new StoryMarkdownService())->parse((string) file_get_contents($fixture));

        $parsed['scenes'][] = [
            'title' => '',
            'environment_description' => '',
            'dialogue_lines' => [
                ['speaker' => 'راوی', 'emotion_tag' => null, 'text' => ''],
            ],
        ];

        $request = UpdateStoryEpisodeRequest::create(
            '/api/admin/story-editor/stories/test/episodes/episode-4',
            'PUT',
            $parsed,
        );
        $request->setContainer($this->app);
        $this->invokePrepareForValidation($request);

        $validator = Validator::make($request->all(), (new UpdateStoryEpisodeRequest())->rules());

        $this->assertFalse($validator->fails(), json_encode($validator->errors()->toArray(), JSON_UNESCAPED_UNICODE));
        $this->assertCount(count($parsed['scenes']) - 1, $request->input('scenes'));
    }

    private function invokePrepareForValidation(UpdateStoryEpisodeRequest $request): void
    {
        $method = new \ReflectionMethod(UpdateStoryEpisodeRequest::class, 'prepareForValidation');
        $method->setAccessible(true);
        $method->invoke($request);
    }
}
