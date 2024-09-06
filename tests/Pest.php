<?php

use HttpAutomock\Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(TestCase::class)->in(__DIR__);
uses(RefreshDatabase::class)->in(__DIR__.'/Unit');
