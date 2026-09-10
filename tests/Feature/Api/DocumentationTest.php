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

    expect($spec['paths'])->toHaveKeys(['/login', '/me', '/me/settings', '/users']);
});

it('documents settings routes in openapi spec', function () {
    $paths = $this->getJson('/docs/api.json')->json('paths');

    expect($paths)->toHaveKeys([
        '/me/settings',
        '/workspace/settings',
        '/admin/settings',
    ]);
});

it('documents admin freelancer routes in openapi spec', function () {
    $paths = $this->getJson('/docs/api.json')->json('paths');

    expect($paths)->toHaveKeys([
        '/admin/freelancers',
        '/admin/freelancers/{freelancer}',
        '/admin/freelancers/{freelancer}/resend-invite',
    ]);
});

it('documents member routes in openapi spec', function () {
    $paths = $this->getJson('/docs/api.json')->json('paths');

    expect($paths)->toHaveKeys([
        '/members',
        '/members/{membership}',
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

it('documents client invoice routes in openapi spec', function () {
    $paths = $this->getJson('/docs/api.json')->json('paths');

    expect($paths)->toHaveKeys([
        '/projects/{project}/client-invoices',
        '/client-invoices/{clientInvoice}',
        '/client-invoices/{clientInvoice}/items',
        '/client-invoices/{clientInvoice}/payments',
    ]);
});

it('documents client member routes in openapi spec', function () {
    $paths = $this->getJson('/docs/api.json')->json('paths');

    expect($paths)->toHaveKey('/clients/{client}/members');
});

it('documents client portal routes in openapi spec', function () {
    $paths = $this->getJson('/docs/api.json')->json('paths');

    expect($paths)->toHaveKeys([
        '/portal/client',
        '/portal/projects',
        '/portal/client-invoices',
    ]);
});

it('documents subscription routes in openapi spec', function () {
    $paths = $this->getJson('/docs/api.json')->json('paths');

    expect($paths)->toHaveKeys([
        '/subscription',
        '/subscription/checkout',
        '/subscription/swap',
        '/subscription/cancel',
        '/webhooks/stripe',
    ]);
});

it('documents admin plan routes in openapi spec', function () {
    $paths = $this->getJson('/docs/api.json')->json('paths');

    expect($paths)->toHaveKeys([
        '/admin/plans',
        '/admin/plans/{plan}',
        '/admin/freelancers/{freelancer}/subscription',
    ]);
});

it('documents reporting routes in openapi spec', function () {
    $paths = $this->getJson('/docs/api.json')->json('paths');

    expect($paths)->toHaveKeys([
        '/workspace/stats',
        '/reports/run',
        '/reports',
        '/reports/{savedReport}',
        '/report-exports',
        '/report-exports/{reportExport}',
        '/admin/reports/platform-stats',
        '/admin/reports/run',
        '/admin/reports',
        '/admin/reports/{savedReport}',
        '/admin/report-exports',
        '/admin/report-exports/{reportExport}',
    ]);
});
