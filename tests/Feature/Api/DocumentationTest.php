<?php

declare(strict_types=1);

it('serves interactive docs ui', function () {
    $this->get('/docs/api')
        ->assertOk()
        ->assertSee('elements-api', false)
        ->assertSee('@stoplight/elements', false);
});

it('serves openapi spec', function () {
    $this->getJson('/docs/api.json')
        ->assertOk()
        ->assertJsonStructure([
            'openapi',
            'info' => ['title', 'version'],
            'paths',
        ])
        ->assertJsonPath('info.title', config('scramble.ui.title'));
});

it('documents authentication routes in openapi spec', function () {
    $spec = $this->getJson('/docs/api.json')->json();

    expect($spec['servers'][0]['url'] ?? '')->toEndWith('/'.config('api.prefix', 'api/v1'));

    expect($spec['paths'])->toHaveKeys(['/login', '/me', '/users']);
});
