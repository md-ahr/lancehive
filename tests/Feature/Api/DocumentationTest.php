<?php

namespace Tests\Feature\Api;

use Tests\TestCase;

class DocumentationTest extends TestCase
{
    public function test_interactive_docs_ui_is_accessible(): void
    {
        $this->get('/docs/api')
            ->assertOk()
            ->assertSee('elements-api', false)
            ->assertSee('@stoplight/elements', false);
    }

    public function test_openapi_spec_is_accessible(): void
    {
        $this->getJson('/docs/api.json')
            ->assertOk()
            ->assertJsonStructure([
                'openapi',
                'info' => ['title', 'version'],
                'paths',
            ])
            ->assertJsonPath('info.title', config('scramble.ui.title'));
    }

    public function test_openapi_spec_documents_authentication_routes(): void
    {
        $spec = $this->getJson('/docs/api.json')->json();

        $this->assertArrayHasKey('/login', $spec['paths']);
        $this->assertArrayHasKey('/me', $spec['paths']);
        $this->assertArrayHasKey('/users', $spec['paths']);
    }
}
