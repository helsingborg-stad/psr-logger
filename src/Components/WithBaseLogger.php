<?php

declare(strict_types=1);

namespace PsrLogger\Components;

use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;
use Psr\Log\NullLogger;
use PsrLogger\Contracts\LoggerFactoryInterface;
use PsrLogger\NullLoggerFactory;
use Stringable;

class WithBaseLogger implements LoggerInterface, LoggerFactoryInterface
{
    public function __construct(
        private LoggerInterface $logger = new NullLogger(),
        private LoggerFactoryInterface $factory = new NullLoggerFactory(),
    ) {}

    public function createLogger(array $args = []): LoggerInterface&LoggerFactoryInterface
    {
        return $this->factory->createLogger($args);
    }

    public function emergency(string|Stringable $message, array $context = []): void
    {
        $this->log(LogLevel::EMERGENCY, $message, $context);
    }

    public function alert(string|Stringable $message, array $context = []): void
    {
        $this->log(LogLevel::ALERT, $message, $context);
    }

    public function critical(string|Stringable $message, array $context = []): void
    {
        $this->log(LogLevel::CRITICAL, $message, $context);
    }

    public function error(string|Stringable $message, array $context = []): void
    {
        $this->log(LogLevel::ERROR, $message, $context);
    }

    public function warning(string|Stringable $message, array $context = []): void
    {
        $this->log(LogLevel::WARNING, $message, $context);
    }

    public function notice(string|Stringable $message, array $context = []): void
    {
        $this->log(LogLevel::NOTICE, $message, $context);
    }

    public function info(string|Stringable $message, array $context = []): void
    {
        $this->log(LogLevel::INFO, $message, $context);
    }

    public function debug(string|Stringable $message, array $context = []): void
    {
        $this->log(LogLevel::DEBUG, $message, $context);
    }

    public function log($level, string|Stringable $message, array $context = []): void
    {
        $this->logger->log($level, $message, [...$context]);
    }
}
