<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Tests\Concerns\ActsAsTenant;

abstract class TestCase extends BaseTestCase
{
    use ActsAsTenant;
    protected function apiUrl(string $uri = ''): string
    {
        $prefix = '/'.config('api.prefix', 'api/v1');
        $uri = ltrim($uri, '/');

        return $uri === '' ? $prefix : $prefix.'/'.$uri;
    }
}
