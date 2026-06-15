<?php

namespace PsrLogger\Tests;

use PHPUnit\Framework\Attributes\TestDox;
use PHPUnit\Framework\TestCase;
use Psr\Log\LogLevel;
use PsrLogger\Client\InMemoryLogger;
use PsrLogger\LoggerFactory;

class LogLevelFilteringTest extends TestCase
{
    private function makeLogger(string $logLevel): array
    {
        $spy = new InMemoryLogger();
        $logger = (new LoggerFactory(loggers: [['logger' => $spy, 'logLevel' => $logLevel]]))->createLogger();
        return [$logger, $spy];
    }

    #[TestDox('a message at the configured threshold is recorded')]
    public function testMessageAtThresholdIsRecorded(): void
    {
        [$logger, $spy] = $this->makeLogger(LogLevel::ERROR);

        $logger->error('at threshold');

        $this->assertCount(1, $spy->records);
    }

    #[TestDox('a message one step below the threshold is suppressed')]
    public function testMessageBelowThresholdIsSuppressed(): void
    {
        [$logger, $spy] = $this->makeLogger(LogLevel::ERROR);

        $logger->warning('below threshold');
        $logger->notice('also below threshold');

        $this->assertCount(0, $spy->records);
    }

    #[TestDox('the default threshold is ERROR')]
    public function testDefaultThresholdIsError(): void
    {
        $spy = new InMemoryLogger();
        $logger = (new LoggerFactory(loggers: [['logger' => $spy]]))->createLogger();

        $logger->error('passes');
        $logger->warning('suppressed');

        $this->assertCount(1, $spy->records);
    }

    #[TestDox('an unknown threshold string falls back to ERROR')]
    public function testUnknownThresholdFallsBackToError(): void
    {
        [$logger, $spy] = $this->makeLogger('unknown-level');

        $logger->error('passes');
        $logger->warning('suppressed');

        $this->assertCount(1, $spy->records);
    }

    #[TestDox('when threshold is EMERGENCY only emergency messages are recorded')]
    public function testEmergencyThresholdPassesOnlyEmergency(): void
    {
        [$logger, $spy] = $this->makeLogger(LogLevel::EMERGENCY);

        $logger->alert('suppressed');
        $logger->critical('suppressed');
        $logger->emergency('passes');

        $this->assertCount(1, $spy->records);
    }
}
