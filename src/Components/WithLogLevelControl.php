<?php

declare(strict_types=1);

namespace PsrLogger\Components;

use Psr\Log\InvalidArgumentException;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use PsrLogger\LogLevelPrio;
use Stringable;

class WithLogLevelControl extends NullLogger implements LoggerInterface
{
    public function __construct(
        private LoggerInterface $logger,
        private int $logLevel = 500,
    ) {}

    public function log($level, string|Stringable $message, array $context = []): void
    {
        if (!array_key_exists($level, LogLevelPrio::LEVELS)) {
            throw new InvalidArgumentException("Unknown log level: {$level}");
        }

        LogLevelPrio::LEVELS[$level] >= $this->logLevel && $this->logger->log($level, $message, [...$context]);
    }
}
