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
            $this->formatMessage($this->preventUnknown
                ? "Tried to send an unknown real request that was prevented."
                : "Tried to send a real request that was prevented."),
            $this->preventUnknown ? 2 : 1
        );
    }

    protected function formatMessage(string $error): string
    {
        return str($error)
            ->append("\n  URL: {$this->request->url()}")
            ->append("\n  Mock file: {$this->mockFilePath}")
            ->value();
    }
}
