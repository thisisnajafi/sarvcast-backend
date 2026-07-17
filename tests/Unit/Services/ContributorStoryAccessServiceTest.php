<?php

namespace Tests\Unit\Services;

use App\Services\ContributorStoryAccessService;
use Tests\TestCase;

class ContributorStoryAccessServiceTest extends TestCase
{
    private ContributorStoryAccessService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(ContributorStoryAccessService::class);
    }

    public function test_normalize_title_strips_english_parenthetical(): void
    {
        $this->assertSame(
            'گاوی و دوستان مزرعه',
            $this->service->normalizeTitle('گاوی و دوستان مزرعه (Gavi and the Happy Farm)'),
        );
    }

    public function test_titles_match_across_editor_and_db_variants(): void
    {
        $this->assertTrue($this->service->titlesMatch(
            'گاوی و دوستان مزرعه (Gavi and the Happy Farm)',
            'گاوی و دوستان مزرعه',
        ));

        $this->assertTrue($this->service->titlesMatch(
            '2 - گاوی و دوستان مزرعه',
            'گاوی و دوستان مزرعه',
        ));

        $this->assertFalse($this->service->titlesMatch('گاوی', 'سیاوش'));
    }
}
