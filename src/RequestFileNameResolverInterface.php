<?php

namespace HttpAutomock;

use Illuminate\Http\Client\Request;

interface RequestFileNameResolverInterface
{
    /**
     * @param  string|callable<Request>|null  $resolutionStrategy
     * @param  Request  $request
     * @param  bool  $forWriting  If the file name is intended to be written to
     * @return string
     */
    function resolve(string|callable|null $resolutionStrategy, Request $request, bool $forWriting): string;
}
