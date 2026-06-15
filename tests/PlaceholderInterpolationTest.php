<?php

namespace PsrLogger\Tests;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\TestDox;
use PHPUnit\Framework\TestCase;
use Psr\Log\LogLevel;
use PsrLogger\Client\InMemoryLogger;
use PsrLogger\LoggerFactory;

class PlaceholderInterpolationTest extends TestCase
{
    private function makeLogger(array $extraConfig = []): array
    {
        $spy = new InMemoryLogger();
        $config = array_merge(['logger' => $spy, 'logLevel' => LogLevel::DEBUG], $extraConfig);
        $logger = (new LoggerFactory('ns', [$config]))->createLogger();
        return [$logger, $spy];
    }

    // ── MUST: placeholder delimiters ({ and }, no whitespace) ────────────────

    #[TestDox('{key} in the message is replaced with its context value')]
    public function testSinglePlaceholderIsReplaced(): void
    {
        [$logger, $spy] = $this->makeLogger();

        $logger->info('Hello {name}', ['name' => 'World']);

        $this->assertStringContainsString('Hello World', $spy->records[0]['message']);
    }

    #[TestDox('multiple {key} placeholders in one message are all replaced')]
    public function testMultiplePlaceholdersAreReplaced(): void
    {
        [$logger, $spy] = $this->makeLogger();

        $logger->info('{greeting} {name}!', ['greeting' => 'Hello', 'name' => 'World']);

        $this->assertStringContainsString('Hello World!', $spy->records[0]['message']);
    }

    #[TestDox('a message with no placeholders is forwarded unchanged')]
    public function testNoPlaceholdersPassesThroughUnchanged(): void
    {
        [$logger, $spy] = $this->makeLogger();

        $logger->info('plain message', ['key' => 'value']);

        $this->assertStringContainsString('plain message', $spy->records[0]['message']);
    }

    #[TestDox('a missing context key leaves the placeholder intact')]
    public function testMissingContextKeyLeavesPlaceholderIntact(): void
    {
        [$logger, $spy] = $this->makeLogger();

        $logger->info('Hello {name}', []);

        $this->assertStringContainsString('Hello {name}', $spy->records[0]['message']);
    }

    #[TestDox('whitespace inside braces is not interpolated')]
    public function testWhitespaceInsideBracesIsNotInterpolated(): void
    {
        [$logger, $spy] = $this->makeLogger();

        $logger->info('Hello { name }', ['name' => 'World']);

        $this->assertStringContainsString('Hello { name }', $spy->records[0]['message']);
    }

    // ── SHOULD: placeholder name characters (A-Z, a-z, 0-9, _, .) ───────────

    #[TestDox('placeholder names with A-Z, a-z, 0-9, and underscores are valid')]
    public function testValidAlphanumericAndUnderscoreName(): void
    {
        [$logger, $spy] = $this->makeLogger();

        $logger->info('{User_ID_42}', ['User_ID_42' => 'abc']);

        $this->assertStringContainsString('abc', $spy->records[0]['message']);
    }

    #[TestDox('{parent.child} resolves to context[parent][child] using dot notation')]
    public function testDotNotationResolvesOneLevel(): void
    {
        [$logger, $spy] = $this->makeLogger();

        $logger->info('Hello {user.name}', ['user' => ['name' => 'Alice']]);

        $this->assertStringContainsString('Hello Alice', $spy->records[0]['message']);
    }

    #[TestDox('{a.b.c} resolves through multiple levels of dot notation')]
    public function testDotNotationResolvesMultipleLevels(): void
    {
        [$logger, $spy] = $this->makeLogger();

        $logger->info('{a.b.c}', ['a' => ['b' => ['c' => 'deep']]]);

        $this->assertStringContainsString('deep', $spy->records[0]['message']);
    }

    #[TestDox('{parent.child} is left intact when the nested key does not exist')]
    public function testDotNotationMissingLeafLeavesPlaceholderIntact(): void
    {
        [$logger, $spy] = $this->makeLogger();

        $logger->info('{user.email}', ['user' => ['name' => 'Alice']]);

        $this->assertStringContainsString('{user.email}', $spy->records[0]['message']);
    }

    #[TestDox('{parent.child} is left intact when the parent key does not exist')]
    public function testDotNotationMissingParentLeavesPlaceholderIntact(): void
    {
        [$logger, $spy] = $this->makeLogger();

        $logger->info('{missing.key}', []);

        $this->assertStringContainsString('{missing.key}', $spy->records[0]['message']);
    }

    #[TestDox('a flat context key takes precedence over dot-notation traversal when both could match')]
    public function testFlatKeyTakesPrecedenceOverDotNotation(): void
    {
        [$logger, $spy] = $this->makeLogger();

        $logger->info('{user.name}', [
            'user.name' => 'flat',
            'user' => ['name' => 'nested'],
        ]);

        $this->assertStringContainsString('flat', $spy->records[0]['message']);
        $this->assertStringNotContainsString('nested', $spy->records[0]['message']);
    }

    #[TestDox('a hyphen in a placeholder name prevents interpolation')]
    public function testHyphenInNamePreventsInterpolation(): void
    {
        [$logger, $spy] = $this->makeLogger();

        $logger->info('{user-name}', ['user-name' => 'Alice']);

        $this->assertStringContainsString('{user-name}', $spy->records[0]['message']);
    }

    // ── Context value rendering ───────────────────────────────────────────────

    public static function scalarContextValueProvider(): array
    {
        return [
            'int' => [42, '42'],
            'float' => [3.14, '3.14'],
        ];
    }

    #[TestDox('int and float context values are cast to string when interpolating')]
    #[DataProvider('scalarContextValueProvider')]
    public function testScalarContextValueIsCastToString(int|float $value, string $expected): void
    {
        [$logger, $spy] = $this->makeLogger();

        $logger->info('{val}', ['val' => $value]);

        $this->assertStringContainsString($expected, $spy->records[0]['message']);
    }

    // ── Complex values ────────────────────────────────────────────────────────

    public static function jsonContextValueProvider(): array
    {
        $obj = new \stdClass();
        $obj->name = 'Alice';
        return [
            'array' => [['foo', 'bar'], json_encode(['foo', 'bar'], JSON_PRETTY_PRINT)],
            'object' => [$obj, json_encode($obj, JSON_PRETTY_PRINT)],
        ];
    }

    #[TestDox('array and non-Stringable object context values are JSON pretty-printed when interpolating')]
    #[DataProvider('jsonContextValueProvider')]
    public function testComplexContextValueIsJsonEncoded(array|object $value, string $expected): void
    {
        [$logger, $spy] = $this->makeLogger();

        $logger->info('{val}', ['val' => $value]);

        $this->assertStringContainsString($expected, $spy->records[0]['message']);
    }

    #[TestDox('a Stringable context value is converted via __toString() when interpolating')]
    public function testStringableContextValue(): void
    {
        [$logger, $spy] = $this->makeLogger();

        $stringable = new class implements \Stringable {
            public function __toString(): string
            {
                return 'stringified';
            }
        };

        $logger->info('Value: {val}', ['val' => $stringable]);

        $this->assertStringContainsString('Value: stringified', $spy->records[0]['message']);
    }

    #[TestDox('a Stringable message is cast to string before placeholder interpolation')]
    public function testStringableMessage(): void
    {
        [$logger, $spy] = $this->makeLogger();

        $message = new class implements \Stringable {
            public function __toString(): string
            {
                return 'Hello {name}';
            }
        };

        $logger->info($message, ['name' => 'World']);

        $this->assertStringContainsString('Hello World', $spy->records[0]['message']);
    }

    // ── Custom resolvers ──────────────────────────────────────────────────────

    #[TestDox('a custom resolver can transform a value that would otherwise be left as a placeholder')]
    public function testCustomResolverTransformsValue(): void
    {
        [$logger, $spy] = $this->makeLogger([
            'resolvers' => [
                ['is_array', fn($v) => implode(',', $v)],
            ],
        ]);

        $logger->info('Tags: {tags}', ['tags' => ['php', 'psr', 'log']]);

        $this->assertStringContainsString('Tags: php,psr,log', $spy->records[0]['message']);
    }

    #[TestDox('the first matching custom resolver wins; later ones are not evaluated')]
    public function testFirstMatchingResolverWins(): void
    {
        [$logger, $spy] = $this->makeLogger([
            'resolvers' => [
                ['is_string', fn($_) => 'first'],
                ['is_string', fn($_) => 'second'],
            ],
        ]);

        $logger->info('{val}', ['val' => 'anything']);

        $this->assertStringContainsString('first', $spy->records[0]['message']);
        $this->assertStringNotContainsString('second', $spy->records[0]['message']);
    }

    #[TestDox('when no custom resolver matches, the placeholder is left intact')]
    public function testNoMatchingResolverLeavesPlaceholderIntact(): void
    {
        [$logger, $spy] = $this->makeLogger([
            'resolvers' => [
                ['is_int', fn($v) => (string) $v],
            ],
        ]);

        $logger->info('{val}', ['val' => 'a string']);

        $this->assertStringContainsString('{val}', $spy->records[0]['message']);
    }
}
