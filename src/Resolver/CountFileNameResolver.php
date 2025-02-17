<?php

namespace HttpAutomock\Resolver;

use Illuminate\Http\Client\Request;

/**
 * Uses an increasing number to name the files.
 *
 * This resolver generates sequential numeric filenames for HTTP request/response pairs.
 * It maintains an internal counter that increments each time a new file is written.
 *
 * Example usage:
 * - First request will be saved as "1"
 * - Second request will be saved as "2"
 * - And so on...
 *
 * Note: The counter only increments when writing files (forWriting = true).
 * When reading files, it returns the current counter value without incrementing.
 */
class CountFileNameResolver implements RequestFileNameResolverInterface
{
    public function __construct(
        public int $count = 1,
    ) {
    }

    function resolve(Request $request, bool $forWriting, string $directory): string
    {
        return $forWriting
            ? $this->count++
            : $this->count;
    }
}
