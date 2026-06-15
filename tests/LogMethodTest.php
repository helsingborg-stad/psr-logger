<?php

namespace PsrLogger\Tests;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\TestDox;
use PHPUnit\Framework\TestCase;
use Psr\Log\InvalidArgumentException;
use Psr\Log\LogLevel;
use PsrLogger\Client\InMemoryLogger;
use PsrLogger\LoggerFactory;

class LogMethodTest extends TestCase
{
    private function makeLogger(): array
    {
        $spy = new InMemoryLogger();
        $logger = (new LoggerFactory(loggers: [['logger' => $spy, 'logLevel' => LogLevel::DEBUG]]))->createLogger();
        return [$logger, $spy];
    }

    public static function levelConstantProvider(): array
    {
        return [
            'emergency' => [LogLevel::EMERGENCY],
            'alert' => [LogLevel::ALERT],
            'critical' => [LogLevel::CRITICAL],
            'error' => [LogLevel::ERROR],
            'warning' => [LogLevel::WARNING],
            'notice' => [LogLevel::NOTICE],
            'info' => [LogLevel::INFO],
            'debug' => [LogLevel::DEBUG],
        ];
    }

    #[DataProvider('levelConstantProvider')]
    #[TestDox('log() with a PSR level constant produces the same record as the dedicated method')]
    public function testLogMethodMatchesDedicatedMethod(string $level): void
    {
        [$loggerA, $spyA] = $this->makeLogger();
        [$loggerB, $spyB] = $this->makeLogger();

        $loggerA->log($level, 'test message');
        $loggerB->$level('test message');

        $this->assertSame($spyA->records[0]['message'], $spyB->records[0]['message']);
        $this->assertSame($spyA->records[0]['level'], $spyB->records[0]['level']);
    }

    #[TestDox('log() with an unknown level throws InvalidArgumentException')]
    public function testUnknownLevelThrowsInvalidArgumentException(): void
    {
        [$logger] = $this->makeLogger();

        $this->expectException(InvalidArgumentException::class);
        $logger->log('not-a-real-level', 'some message');
    }
}
