<?php

use Illuminate\Foundation\Testing\RefreshDatabase;

require_once __DIR__.'/TestHelpers.php';

uses(Tests\TestCase::class, RefreshDatabase::class)->in('Feature');
uses(Tests\TestCase::class)->in('Unit');
