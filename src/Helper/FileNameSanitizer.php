<?php

namespace HttpAutomock\Helper;

use Illuminate\Support\Stringable;

class FileNameSanitizer
{
    public static function sanitize(string|Stringable $value, string $with = '-'): string|Stringable
    {
        static $replace = [':', '?', '*', '"', '<', '>', '|', '\r', '\n', '\t'];

        return $value instanceof Stringable
            ? $value->replace($replace, $with)
            : str($value)->replace($replace, $with)->value();
    }
}
