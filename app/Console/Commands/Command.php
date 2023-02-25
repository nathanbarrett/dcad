<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command as ConsoleCommand;
abstract class Command extends ConsoleCommand
{
    protected function tsLine(string $message, int|string|null $verbosity = null): void
    {
        $this->tsOutput('line', $message, $verbosity);
    }

    protected function tsComment(string $message, int|string|null $verbosity = null): void
    {
        $this->tsOutput('comment', $message, $verbosity);
    }

    protected function tsInfo(string $message, int|string|null $verbosity = null): void
    {
        $this->tsOutput('info', $message, $verbosity);
    }

    protected function tsWarn(string $message, int|string|null $verbosity = null): void
    {
        $this->tsOutput('warn', $message, $verbosity);
    }

    protected function tsError(string $message, int|string|null $verbosity = null): void
    {
        $this->tsOutput('error', $message, $verbosity);
    }

    protected function tsAlert(string $message, int|string|null $verbosity = null): void
    {
        $this->tsOutput('alert', $message, $verbosity);
    }

    private function tsOutput(string $outputType, string $message, int|string|null $verbosity = null): void
    {
        $this->$outputType("\n" . now()->toIso8601String() . ": {$message}", $verbosity);
    }
}
