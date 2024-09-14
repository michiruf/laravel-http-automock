<?php

namespace HttpAutomock\Serialization;

use Illuminate\Http\Client\Request;

interface RequestFileNameResolverInterface
{
    /**
     * @param  string|callable<Request>|null  $resolutionStrategy  Strategy used to resolve the file name
     * @param  Request  $request  The request used to resolve the filename
     * @param  bool  $forWriting  If the file name is intended to be written to
     * @return string File name
     */
    function resolve(string|callable|null $resolutionStrategy, Request $request, bool $forWriting): string;
}
