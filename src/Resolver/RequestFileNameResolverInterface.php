<?php

namespace HttpAutomock\Resolver;

use Illuminate\Http\Client\Request;

interface RequestFileNameResolverInterface
{
    /**
     * @param  Request  $request  The request used to resolve the filename
     * @param  bool  $forWriting  If the file name is intended to be written to
     * @return string File name
     */
    function resolve(Request $request, bool $forWriting): string;
}
