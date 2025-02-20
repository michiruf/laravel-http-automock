<?php

namespace HttpAutomock\Tests\TestServer;

use Illuminate\Process\InvokedProcess;
use Illuminate\Support\Facades\Process;

class TestServer
{
    protected static ?InvokedProcess $process = null;

    public static function start(): void
    {
        // Starting the process via array syntax is required that the process does not use a shell
        // to execute the command resulting the server cannot be stopped properly
        static::$process = Process::path(__DIR__)
            ->forever()
            ->start(['php', '-S', 'localhost:9337', 'server.php']);

        // Wait until the server fully started
        // Example log:
        // > [Sun Feb 16 15:21:44 2025] PHP 8.3.12 Development Server (http://localhost:9337) started
        static::$process->waitUntil(function (string $type, string $output) {
            return str($output)->contains('started');
        });
    }

    public static function stop(): void
    {
        static::$process?->stop();
    }
}
