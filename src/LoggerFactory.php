<?php

declare(strict_types=1);

namespace PsrLogger;

use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;
use Psr\Log\NullLogger;
use PsrLogger\Components\WithBaseLogger;
use PsrLogger\Components\WithComposite;
use PsrLogger\Components\WithContextPlaceholders;
use PsrLogger\Components\WithFormatter;
use PsrLogger\Components\WithLogLevelControl;
use PsrLogger\Contracts\LoggerFactoryInterface;

class LoggerFactory implements LoggerFactoryInterface
{
    public function __construct(
        private string $namespace = 'PluginNameSpace',
        private $loggers = [
            [
                'logger' => new NullLogger(),
                'logLevel' => LogLevel::ERROR,
            ],
        ],
        private string $logLevel = LogLevel::ERROR,
        private ?array $breadcrumbs = null,
    ) {
        $this->breadcrumbs = $this->effectivePath($namespace, $breadcrumbs ?? []);
    }

    public function createLogger(array $args = []): LoggerInterface&LoggerFactoryInterface
    {
        return new WithBaseLogger(
            new WithComposite(array_map(fn($loggerConfiguration) => $this->composeFromConfig([
                ...[
                    'logLevel' => $this->logLevel,
                    'namespace' => $this->namespace,
                    'breadcrumbs' => $this->breadcrumbs,
                ],
                ...$loggerConfiguration,
                ...$this->removeUnsafeOverridables($args),
            ]), $this->loggers)),
            new self($args['namespace'] ?? $this->namespace, $this->loggers, $this->logLevel, $this->breadcrumbs),
        );
    }

    private function composeFromConfig(array $args): LoggerInterface
    {
        return new WithLogLevelControl(
            new WithFormatter(
                new WithContextPlaceholders(
                    $args['logger'],
                    $args['resolvers'] ?? null,
                    $args['jsonResolverFlag'] ?? null,
                ),
                $this->effectivePath($args['namespace'], $args['breadcrumbs']),
                $args['breadcrumbDirection'] ?? null,
                $args['breadcrumbMaxCount'] ?? null,
                $args['formatStr'] ?? null,
            ),
            LogLevelPrio::LEVELS[$args['logLevel'] ?? LogLevel::ERROR] ?? LogLevelPrio::LEVELS[LogLevel::ERROR],
        );
    }

    private function removeUnsafeOverridables(array $args)
    {
        unset($args['breadcrumbs']);
        unset($args['logger']);
        unset($args['logLevel']);
        return $args;
    }

    private function effectivePath(string $namespace, array $breadcrumbs)
    {
        $effectivePath = [...$breadcrumbs];
        if (empty($effectivePath) || $effectivePath[array_key_last($effectivePath)] !== $namespace) {
            $effectivePath[] = $namespace;
        }

        return $effectivePath;
    }
}
