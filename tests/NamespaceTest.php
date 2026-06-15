<?php

namespace PsrLogger\Tests;

use PHPUnit\Framework\Attributes\TestDox;
use PHPUnit\Framework\TestCase;
use Psr\Log\LogLevel;
use PsrLogger\Client\InMemoryLogger;
use PsrLogger\LoggerFactory;

class NamespaceTest extends TestCase
{
    private function makeFactory(string $namespace, array $extraConfig = []): array
    {
        $spy = new InMemoryLogger();
        $config = array_merge(['logger' => $spy, 'logLevel' => LogLevel::DEBUG], $extraConfig);
        return [new LoggerFactory($namespace, [$config]), $spy];
    }

    private function threeLevel(array $extraConfig): array
    {
        $spy = new InMemoryLogger();
        $config = array_merge(['logger' => $spy, 'logLevel' => LogLevel::DEBUG], $extraConfig);
        $factory = new LoggerFactory('App', [$config]);
        $app = $factory->createLogger();
        $child = $app->createLogger(['namespace' => 'Module'])->createLogger();
        $grandchild = $child->createLogger(['namespace' => 'Component'])->createLogger();
        return [$grandchild, $spy];
    }

    #[TestDox('root logger labels messages with its configured namespace')]
    public function testRootLoggerUsesConfiguredNamespace(): void
    {
        [$factory, $spy] = $this->makeFactory('App');

        $factory->createLogger()->debug('test');

        $this->assertStringContainsString('[App]', $spy->records[0]['message']);
    }

    #[TestDox('child logger shows both parent and child namespace segments in the message')]
    public function testChildLoggerAppendsNamespaceSegment(): void
    {
        [$factory, $spy] = $this->makeFactory('App');
        $app = $factory->createLogger();

        $app->createLogger(['namespace' => 'Module'])->createLogger()->debug('test');

        $this->assertStringContainsString('[App/Module]', $spy->records[0]['message']);
    }

    #[TestDox('two children of the same parent produce independent namespace paths')]
    public function testIndependentNamespaceBranches(): void
    {
        [$factory, $spy] = $this->makeFactory('App');
        $app = $factory->createLogger();

        $app->createLogger(['namespace' => 'A'])->createLogger()->debug('branch-a');
        $app->createLogger(['namespace' => 'B'])->createLogger()->debug('branch-b');

        $this->assertStringContainsString('[App/A]', $spy->records[0]['message']);
        $this->assertStringContainsString('[App/B]', $spy->records[1]['message']);
    }

    #[TestDox('right trimming keeps the earliest (root) segment in a multi-level hierarchy')]
    public function testRightTrimmingKeepsRootSegment(): void
    {
        [$grandchild, $spy] = $this->threeLevel(['breadcrumbDirection' => 'right', 'breadcrumbMaxCount' => 1]);

        $grandchild->debug('test');

        $this->assertStringContainsString('[App]', $spy->records[0]['message']);
    }

    #[TestDox('left trimming keeps the latest (leaf) segment in a multi-level hierarchy')]
    public function testLeftTrimmingKeepsLeafSegment(): void
    {
        [$grandchild, $spy] = $this->threeLevel(['breadcrumbDirection' => 'left', 'breadcrumbMaxCount' => 1]);

        $grandchild->debug('test');

        $this->assertStringContainsString('[Component]', $spy->records[0]['message']);
    }

    #[TestDox('when maxCount exceeds path depth, all segments appear')]
    public function testMaxCountExceedingDepthShowsAllSegments(): void
    {
        [$factory, $spy] = $this->makeFactory('App', ['breadcrumbMaxCount' => 10]);
        $app = $factory->createLogger();

        $app->createLogger(['namespace' => 'Module'])->createLogger()->debug('test');

        $this->assertStringContainsString('[App/Module]', $spy->records[0]['message']);
    }

    #[TestDox('maxCount of zero is treated as one segment')]
    public function testMaxCountZeroTreatedAsOne(): void
    {
        [$grandchild, $spy] = $this->threeLevel(['breadcrumbDirection' => 'left', 'breadcrumbMaxCount' => 0]);

        $grandchild->debug('test');

        $this->assertStringContainsString('[Component]', $spy->records[0]['message']);
    }

    #[TestDox('an invalid direction falls back to right-trim behaviour')]
    public function testInvalidDirectionFallsBackToRight(): void
    {
        [$grandchildRight, $spyRight] = $this->threeLevel([
            'breadcrumbDirection' => 'right',
            'breadcrumbMaxCount' => 1,
        ]);
        [$grandchildInvalid, $spyInvalid] = $this->threeLevel([
            'breadcrumbDirection' => 'invalid',
            'breadcrumbMaxCount' => 1,
        ]);

        $grandchildRight->debug('test');
        $grandchildInvalid->debug('test');

        $this->assertSame($spyRight->records[0]['message'], $spyInvalid->records[0]['message']);
    }
}
