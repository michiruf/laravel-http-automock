<?php

namespace HttpAutomock\Pest;

use HttpAutomock\HttpAutomockOptions;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Pest\Contracts\Plugins\HandlesArguments;

/**
 * This plugin removes all automock arguments from pests CLI because otherwise
 * pest would throw an error that the options are unknown.
 *
 * @see          HttpAutomockOptions How the options retrieved
 *
 * @noinspection PhpUnused
 */
class PestAutomockPlugin implements HandlesArguments
{
    public function handleArguments(array $arguments): array
    {
        $removedAutomockArgs = Arr::where($arguments, fn ($argument) => ! Str::startsWith($argument, '--automock-'));

        return array_values($removedAutomockArgs);
    }
}
