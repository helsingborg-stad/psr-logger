<?php

declare(strict_types=1);

namespace PsrLogger\Contracts;

use Psr\Log\LoggerInterface;

interface LoggerFactoryInterface
{
    public function createLogger(array $args = []): LoggerInterface&LoggerFactoryInterface;
}
