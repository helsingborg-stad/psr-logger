<?php

namespace PsrLogger\Tests;

use PHPUnit\Framework\Attributes\TestDox;
use PHPUnit\Framework\TestCase;
use Psr\Log\LogLevel;
use PsrLogger\Client\InMemoryLogger;
use PsrLogger\LoggerFactory;

class MessageFormattingTest extends TestCase
{
    private function makeLogger(array $extraConfig = []): array
    {
        $spy = new InMemoryLogger();
        $config = array_merge(['logger' => $spy, 'logLevel' => LogLevel::DEBUG], $extraConfig);
        $logger = (new LoggerFactory('my-ns', [$config]))->createLogger();
        return [$logger, $spy];
    }

    #[TestDox('the recorded message contains the log level')]
    public function testMessageContainsLevel(): void
    {
        [$logger, $spy] = $this->makeLogger();

        $logger->info('test');

        $this->assertStringContainsString('INFO', $spy->records[0]['message']);
    }

    #[TestDox('the recorded message contains the namespace')]
    public function testMessageContainsNamespace(): void
    {
        [$logger, $spy] = $this->makeLogger();

        $logger->info('test');

        $this->assertStringContainsString('my-ns', $spy->records[0]['message']);
    }

    #[TestDox('the recorded message contains the original message text')]
    public function testMessageContainsOriginalText(): void
    {
        [$logger, $spy] = $this->makeLogger();

        $logger->info('original text here');

        $this->assertStringContainsString('original text here', $spy->records[0]['message']);
    }

    #[TestDox('a Stringable value can be passed as the message')]
    public function testStringableMessage(): void
    {
        [$logger, $spy] = $this->makeLogger();

        $message = new class implements \Stringable {
            public function __toString(): string
            {
                return 'from stringable';
            }
        };

        $logger->info($message);

        $this->assertStringContainsString('from stringable', $spy->records[0]['message']);
    }

    #[TestDox('a custom format string reshapes the output')]
    public function testCustomFormatStringIsApplied(): void
    {
        [$logger, $spy] = $this->makeLogger(['formatStr' => '%2$s|%1$s|%3$s']);

        $logger->info('hello');

        $this->assertSame('my-ns|INFO|hello', $spy->records[0]['message']);
    }
}
