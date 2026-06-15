<?php

namespace PsrLogger\Tests;

use PHPUnit\Framework\Attributes\TestDox;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;
use Psr\Log\NullLogger;
use PsrLogger\Client\InMemoryLogger;
use PsrLogger\Contracts\LoggerFactoryInterface;
use PsrLogger\LoggerFactory;

class ChildLoggerTest extends TestCase
{
    #[TestDox('createLogger() returns an object implementing LoggerInterface')]
    public function testCreateLoggerReturnsLoggerInterface(): void
    {
        $child = (new LoggerFactory())->createLogger();

        $this->assertInstanceOf(LoggerInterface::class, $child);
    }

    #[TestDox('createLogger() returns an object implementing LoggerFactoryInterface')]
    public function testCreateLoggerReturnsFactoryInterface(): void
    {
        $child = (new LoggerFactory())->createLogger();

        $this->assertInstanceOf(LoggerFactoryInterface::class, $child);
    }

    #[TestDox('the logger configured on the factory cannot be swapped out via createLogger() args')]
    public function testLoggerCannotBeOverriddenViaArgs(): void
    {
        $spy = new InMemoryLogger();
        $logger = (new LoggerFactory(loggers: [['logger' => $spy, 'logLevel' => LogLevel::DEBUG]]))->createLogger([
            'logger' => new NullLogger(),
        ]);

        $logger->debug('test');

        $this->assertCount(1, $spy->records);
    }

    #[TestDox('the logLevel configured on the factory cannot be raised via createLogger() args')]
    public function testLogLevelCannotBeOverriddenViaArgs(): void
    {
        $spy = new InMemoryLogger();
        $logger = (new LoggerFactory(loggers: [['logger' => $spy, 'logLevel' => LogLevel::DEBUG]]))->createLogger([
            'logLevel' => LogLevel::EMERGENCY,
        ]);

        $logger->debug('still passes');

        $this->assertCount(1, $spy->records);
    }
}
