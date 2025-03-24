<?php

namespace Workbench\App\Resolver;

use HttpAutomock\Resolver\FileNameResolverInterface;
use Illuminate\Http\Client\Request;
use RuntimeException;

class ExceptionTestResolver implements FileNameResolverInterface
{
    public function resolve(Request $request, bool $forWriting, string $directory): string
    {
        throw new RuntimeException("Test");
    }
}
