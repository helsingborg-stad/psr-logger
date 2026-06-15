<?php

declare(strict_types=1);

namespace PsrLogger;

use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use PsrLogger\Contracts\LoggerFactoryInterface;

class NullLoggerFactory extends NullLogger implements LoggerFactoryInterface
{
    public function createLogger(array $args = []): LoggerInterface&LoggerFactoryInterface
    {
        return new self();
    }
}
