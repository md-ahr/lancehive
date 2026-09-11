<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Rate Limit Cache Store
    |--------------------------------------------------------------------------
    |
    | Throttle counters are stored separately from application cache data.
    | Use the Redis store in Sail/production so counters survive requests
    | and do not compete with PostgreSQL. PHPUnit uses array via phpunit.xml.
    |
    */

    'store' => env('RATE_LIMIT_STORE', env('CACHE_STORE', 'redis')),

    /*
    |--------------------------------------------------------------------------
    | Named limiters (requests per minute unless noted)
    |--------------------------------------------------------------------------
    */

    'limits' => [
        'api' => (int) env('RATE_LIMIT_API', 120),
        'login' => (int) env('RATE_LIMIT_LOGIN', 5),
        'password_reset' => (int) env('RATE_LIMIT_PASSWORD_RESET', 3),
        'tenant_writes' => (int) env('RATE_LIMIT_TENANT_WRITES', 60),
        'reports' => (int) env('RATE_LIMIT_REPORTS', 10),
        'admin' => (int) env('RATE_LIMIT_ADMIN', 120),
        'subscription' => (int) env('RATE_LIMIT_SUBSCRIPTION', 10),
        'webhooks' => (int) env('RATE_LIMIT_WEBHOOKS', 120),
    ],

    /*
    |--------------------------------------------------------------------------
    | Plan-aware tenant write multipliers (Layer 2 — Redis)
    |--------------------------------------------------------------------------
    |
    | Applied to the tenant-writes limiter using the workspace subscription plan.
    | Example: starter ×1, pro ×2, enterprise ×3 on a base of 60/min → 60/120/180.
    |
    */

    'plan_multipliers' => [
        'starter' => (float) env('RATE_LIMIT_PLAN_STARTER_MULTIPLIER', 1),
        'pro' => (float) env('RATE_LIMIT_PLAN_PRO_MULTIPLIER', 2),
        'enterprise' => (float) env('RATE_LIMIT_PLAN_ENTERPRISE_MULTIPLIER', 3),
    ],

    /*
    |--------------------------------------------------------------------------
    | Nginx edge limits (Layer 1 — reference for production)
    |--------------------------------------------------------------------------
    */

    'nginx' => [
        'requests_per_second' => (int) env('NGINX_RATE_LIMIT_RPS', 50),
        'burst' => (int) env('NGINX_RATE_LIMIT_BURST', 100),
    ],

];
