<?php

declare(strict_types=1);

namespace PsrLogger\Client;

use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Stringable;

class PhpErrorLogger extends NullLogger implements LoggerInterface
{
    public function log($level, string|Stringable $message, array $context = []): void
    {
        error_log($message);
    }
}
