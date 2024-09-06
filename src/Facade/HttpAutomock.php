<?php

namespace HttpAutomock\Facade;

use HttpAutomock\HttpAutomock as BaseHttpAutomock;
use Illuminate\Support\Facades\Facade;

/**
 * @method BaseHttpAutomock enable():
 * @method void disable():
 */
class HttpAutomock extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return BaseHttpAutomock::class;
    }
}
