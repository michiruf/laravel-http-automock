<?php

namespace HttpAutomock\Support;

use GuzzleHttp\Psr7\Query;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Collection;

class RequestMixin
{
    /** @noinspection PhpUnused */
    public function collectQuery(): callable
    {
        return function (): Collection {
            /** @var Request $this */
            return collect($this->query());
        };
    }

    /** @noinspection PhpUnused */
    public function query(): callable
    {
        return function (): array {
            return Query::parse($this->toPsrRequest()->getUri()->getQuery());
        };
    }
}
