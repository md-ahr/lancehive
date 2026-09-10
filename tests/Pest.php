<?php

require __DIR__.'/../vendor/autoload.php';

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

pest()->extend(TestCase::class)
    ->in('Feature', 'Unit');

uses(RefreshDatabase::class)->in('Feature', 'Unit/Auth');
