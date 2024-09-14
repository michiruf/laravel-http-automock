<?php

namespace HttpAutomock\Facades;

use HttpAutomock\HttpAutomock as BaseHttpAutomock;
use Illuminate\Support\Facades\Facade;

/**
 * @mixin BaseHttpAutomock
 */
class HttpAutomock extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return BaseHttpAutomock::class;
    }
}
