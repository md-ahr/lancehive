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

it('documents admin freelancer routes in openapi spec', function () {
    $paths = $this->getJson('/docs/api.json')->json('paths');

    expect($paths)->toHaveKeys([
        '/admin/freelancers',
        '/admin/freelancers/{freelancer}',
        '/admin/freelancers/{freelancer}/resend-invite',
    ]);
});

it('documents client routes in openapi spec', function () {
    $paths = $this->getJson('/docs/api.json')->json('paths');

    expect($paths)->toHaveKeys([
        '/clients',
        '/clients/{client}',
    ]);
});

it('documents project routes in openapi spec', function () {
    $paths = $this->getJson('/docs/api.json')->json('paths');

    expect($paths)->toHaveKeys([
        '/projects',
        '/projects/{project}',
        '/clients/{client}/projects',
    ]);
});

it('documents task routes in openapi spec', function () {
    $paths = $this->getJson('/docs/api.json')->json('paths');

    expect($paths)->toHaveKeys([
        '/projects/{project}/tasks',
        '/tasks/{task}',
    ]);
});

it('documents time log routes in openapi spec', function () {
    $paths = $this->getJson('/docs/api.json')->json('paths');

    expect($paths)->toHaveKeys([
        '/tasks/{task}/time-logs',
        '/time-logs/{timeLog}',
    ]);
});

it('documents project time summary route in openapi spec', function () {
    $paths = $this->getJson('/docs/api.json')->json('paths');

    expect($paths)->toHaveKey('/projects/{project}/time-summary');
});
