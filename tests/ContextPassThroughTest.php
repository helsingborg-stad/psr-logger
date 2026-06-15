<?php

namespace PsrLogger\Tests;

use PHPUnit\Framework\Attributes\TestDox;
use PHPUnit\Framework\TestCase;
use Psr\Log\LogLevel;
use PsrLogger\Client\InMemoryLogger;
use PsrLogger\LoggerFactory;

class ContextPassThroughTest extends TestCase
{
    private function makeLogger(): array
    {
        $spy = new InMemoryLogger();
        $logger = (new LoggerFactory(loggers: [['logger' => $spy, 'logLevel' => LogLevel::DEBUG]]))->createLogger();
        return [$logger, $spy];
    }

    #[TestDox('the context array is available unchanged at the destination')]
    public function testContextIsAvailableAtDestination(): void
    {
        [$logger, $spy] = $this->makeLogger();

        $logger->error('msg', ['foo' => 'bar']);

        $this->assertSame(['foo' => 'bar'], $spy->records[0]['context']);
    }

    #[TestDox('the log level is available unchanged at the destination')]
    public function testLogLevelIsAvailableAtDestination(): void
    {
        [$logger, $spy] = $this->makeLogger();

        $logger->info('msg');

        $this->assertSame(LogLevel::INFO, $spy->records[0]['level']);
    }
}
