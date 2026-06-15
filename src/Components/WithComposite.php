<?php

declare(strict_types=1);

namespace PsrLogger\Components;

use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Stringable;

class WithComposite extends NullLogger implements LoggerInterface
{
    /**
     * @param LoggerInterface[] $loggers
     */
    public function __construct(
        private array $loggers,
    ) {}

    public function log($level, string|Stringable $message, array $context = []): void
    {
        foreach ($this->loggers as $logger) {
            $logger->log($level, $message, $context);
        }
    }
}
