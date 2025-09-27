<?php

namespace HttpAutomock\Support;

use HttpAutomock\Facades\HttpAutomock as HttpAutomockFacade;
use HttpAutomock\HttpAutomock;

class HttpAutomockMixin
{
    /** @noinspection PhpUnused */
    public function automock(): callable
    {
        /**
         * Automatically mock requests.
         */
        return function (): HttpAutomock {
            return HttpAutomockFacade::enable();
        };
    }

    /** @noinspection PhpUnused */
    public function noAutomock(): callable
    {
        /**
         * Disable automatically mocking requests.
         */
        return function (): HttpAutomock {
            return HttpAutomockFacade::disable();
        };
    }

    /** @noinspection PhpUnused */
    public function configureAutomock(): callable
    {
        /**
         * Return the automock instance to configure it.
         */
        return function (): HttpAutomock {
            return HttpAutomockFacade::getFacadeRoot();
        };
    }

    /** @noinspection PhpUnused */
    public function automockEnabled(): callable
    {
        /**
         * Return whether automock is enabled.
         */
        return function (): bool {
            return HttpAutomockFacade::enabled();
        };
    }
}
