<?php

namespace Tests\Feature\Admin;

use Tests\TestCase;

/**
 * HTTP smoke checks that do not require RefreshDatabase
 * (full migrate is MySQL-oriented and breaks on sqlite :memory:).
 */
class LocalImportOldStoriesApiTest extends TestCase
{
    public function test_import_old_requires_auth(): void
    {
        $response = $this->postJson('/api/admin/local-import/stories/import-old');

        $response->assertStatus(401);
    }
}
