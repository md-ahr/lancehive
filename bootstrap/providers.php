<?php

use App\Providers\AppServiceProvider;
use App\Providers\CacheInvalidationServiceProvider;
use App\Providers\RateLimitServiceProvider;

return [
    AppServiceProvider::class,
    RateLimitServiceProvider::class,
    CacheInvalidationServiceProvider::class,
];
