<?php

it('returns the API root payload', function () {
    $this->get('/')
        ->assertOk()
        ->assertJson([
            'status' => 'ok',
            'name' => config('app.name'),
            'api' => '/'.config('api.prefix'),
            'docs' => '/docs/api',
            'health' => '/up',
        ]);
});
