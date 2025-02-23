<?php

namespace HttpAutomock\Exceptions;

use Illuminate\Http\Client\Request;
use RuntimeException;

class PreventedRequestException extends RuntimeException
{
    public function __construct(
        public Request $request,
        public string $mockFilePath,
        public bool $preventUnknown,
    ) {
        parent::__construct(
            $this->preventUnknown
                ? "Tried to send an unknown real request that was prevented, url: {$request->url()}, mock file: $mockFilePath"
                : "Tried to send a real request that was prevented, url: {$request->url()}, mock file: $mockFilePath",
            $this->preventUnknown ? 2 : 1
        );
    }
}