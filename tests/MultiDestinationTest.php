<?php

namespace PsrLogger\Tests;

use PHPUnit\Framework\Attributes\TestDox;
use PHPUnit\Framework\TestCase;
use Psr\Log\LogLevel;
use PsrLogger\Client\InMemoryLogger;
use PsrLogger\LoggerFactory;

class MultiDestinationTest extends TestCase
{
    private function makeDualLogger(): array
    {
        $spy1 = new InMemoryLogger();
        $spy2 = new InMemoryLogger();
        $logger = (new LoggerFactory(loggers: [
            ['logger' => $spy1, 'logLevel' => LogLevel::DEBUG],
            ['logger' => $spy2, 'logLevel' => LogLevel::DEBUG],
        ]))->createLogger();
        return [$logger, $spy1, $spy2];
    }

    #[TestDox('a message is forwarded to every configured destination')]
    public function testMessageReachesAllDestinations(): void
    {
        [$logger, $spy1, $spy2] = $this->makeDualLogger();

        $logger->info('hello');

        $this->assertCount(1, $spy1->records);
        $this->assertCount(1, $spy2->records);
    }

    #[TestDox('every destination receives the same log level')]
    public function testAllDestinationsReceiveSameLevel(): void
    {
        [$logger, $spy1, $spy2] = $this->makeDualLogger();

        $logger->error('boom');

        $this->assertSame(LogLevel::ERROR, $spy1->records[0]['level']);
        $this->assertSame(LogLevel::ERROR, $spy2->records[0]['level']);
    }

    #[TestDox('every destination receives the same message text')]
    public function testAllDestinationsReceiveSameMessage(): void
    {
        [$logger, $spy1, $spy2] = $this->makeDualLogger();

        $logger->warning('watch out');

        $this->assertSame($spy1->records[0]['message'], $spy2->records[0]['message']);
    }

    #[TestDox('every destination receives the same context')]
    public function testAllDestinationsReceiveSameContext(): void
    {
        [$logger, $spy1, $spy2] = $this->makeDualLogger();

        $logger->debug('msg', ['key' => 'value']);

        $this->assertSame(['key' => 'value'], $spy1->records[0]['context']);
        $this->assertSame(['key' => 'value'], $spy2->records[0]['context']);
    }

    #[TestDox('each destination filters independently when thresholds differ')]
    public function testPerDestinationThresholdFiltersIndependently(): void
    {
        $spyError = new InMemoryLogger();
        $spyEmergency = new InMemoryLogger();
        $logger = (new LoggerFactory(loggers: [
            ['logger' => $spyError, 'logLevel' => LogLevel::ERROR],
            ['logger' => $spyEmergency, 'logLevel' => LogLevel::EMERGENCY],
        ]))->createLogger();

        $logger->error('error-level');
        $logger->emergency('emergency-level');

        $this->assertCount(2, $spyError->records);
        $this->assertCount(1, $spyEmergency->records);
    }
}
