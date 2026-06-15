<?php

declare(strict_types=1);

namespace PsrLogger\Client;

use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Stringable;

class InMemoryLogger extends NullLogger implements LoggerInterface
{
    public array $records = [];

    public function log($level, string|Stringable $message, array $context = []): void
    {
        $this->records[] = compact('level', 'message', 'context');
    }
}
