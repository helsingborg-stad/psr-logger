<?php

declare(strict_types=1);

namespace PsrLogger\Components;

use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Stringable;

class WithFormatter extends NullLogger implements LoggerInterface
{
    public function __construct(
        private LoggerInterface $logger,
        private array $namespace,
        private ?string $breadcrumbDirection = 'left',
        private ?int $breadcrumbMaxCount = -1,
        private ?string $formatStr = null,
    ) {}

    public function log($level, string|Stringable $message, array $context = []): void
    {
        $args = [
            \strtoupper($level),
            $this->formatNs($this->namespace),
            $message,
        ];

        $this->logger->log($level, \sprintf($this->formatStr ?? '[%s]:[%s]: %s', ...$args), $context);
    }

    private function formatNs(array $namespace): string
    {
        return implode('/', $this->breadcrumbPaths(
            $namespace,
            $this->breadcrumbDirection ?? 'left',
            $this->breadcrumbMaxCount ?? -1,
        ));
    }

    private function breadcrumbPaths(array $paths, string $direction, int $maxCount): array
    {
        if (-1 === $maxCount)
            return $paths;
        $maxCount = 0 >= $maxCount ? 1 : $maxCount;
        $count = count($paths);
        return match (true) {
            $maxCount <= 0, $count === 0 => [],
            $count <= $maxCount => $paths,
            $maxCount === 1 => [
                $paths[$direction === 'left' ? $count - 1 : 0],
            ],
            default => array_merge(
                [$paths[0]],
                array_slice(array_slice($paths, 1, -1), $direction === 'left' ? -($maxCount - 2) : 0, $maxCount - 2),
                [$paths[$count - 1]],
            ),
        };
    }
}
