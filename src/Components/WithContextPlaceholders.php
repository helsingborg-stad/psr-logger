<?php

declare(strict_types=1);

namespace PsrLogger\Components;

use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Stringable;

class WithContextPlaceholders extends NullLogger implements LoggerInterface
{
    private array $resolvers;

    public function __construct(
        private LoggerInterface $logger,
        ?array $resolvers = null,
        ?int $jsonResolverFlag = null,
    ) {
        $this->resolvers = $resolvers ?? [
            ['is_string', static fn($v) => $v],
            ['is_int', static fn($v) => (string) $v],
            ['is_float', static fn($v) => (string) $v],
            [static fn($v) => $v instanceof Stringable, static fn($v) => (string) $v],
            ['is_array', static fn($v) => json_encode($v, $jsonResolverFlag ?? JSON_PRETTY_PRINT)],
            ['is_object', static fn($v) => json_encode($v, $jsonResolverFlag ?? JSON_PRETTY_PRINT)],
        ];
    }

    public function log($level, string|Stringable $message, array $context = []): void
    {
        $this->logger->log($level, $this->interpolate((string) $message, $context), $context);
    }

    private function interpolate(string $message, array $context): string
    {
        return preg_replace_callback(
            '/\{([A-Za-z0-9_.]+)\}/',
            function (array $matches) use ($context): string {
                $value = $this->resolve($matches[1], $context);
                return $this->applyResolvers($value) ?? $matches[0];
            },
            $message,
        );
    }

    private function applyResolvers(mixed $value): ?string
    {
        foreach ($this->resolvers as [$valid, $transform]) {
            if ($valid($value)) {
                return $transform($value);
            }
        }
        return null;
    }

    private function resolve(string $key, array $context): mixed
    {
        if (array_key_exists($key, $context)) {
            return $context[$key];
        }

        $parts = explode('.', $key);
        $current = $context;
        foreach ($parts as $part) {
            if (!is_array($current) || !array_key_exists($part, $current)) {
                return null;
            }
            $current = $current[$part];
        }

        return $current;
    }
}
