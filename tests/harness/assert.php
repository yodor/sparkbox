<?php
/**
 * Minimal test registry and assertions.
 * A test file registers tests with test(); runfile.php runs them and prints one line per test:
 *   PASS <name>
 *   FAIL <name> | <message>
 *   SKIP <name> | <reason>
 */

class TestSkipped extends Exception {}
class TestFailed extends Exception {}

final class Tests
{
    /** @var array<string, Closure> */
    public static array $registered = array();
}

function test(string $name, Closure $fn) : void
{
    Tests::$registered[$name] = $fn;
}

function skip(string $reason) : never
{
    throw new TestSkipped($reason);
}

function assertTrue(bool $condition, string $message = "expected true") : void
{
    if (!$condition) throw new TestFailed($message);
}

function assertSame(mixed $expected, mixed $actual, string $message = "") : void
{
    if ($expected !== $actual) {
        throw new TestFailed(($message ? "$message: " : "") . "expected " . ShortValue($expected) . " got " . ShortValue($actual));
    }
}

function ShortValue(mixed $value) : string
{
    $text = json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    return (strlen($text) > 160) ? substr($text, 0, 160) . "…" : $text;
}

function assertThrows(Closure $fn, string $contains = "") : void
{
    try {
        $fn();
    }
    catch (Throwable $e) {
        if ($contains !== "" && !str_contains($e->getMessage(), $contains)) {
            throw new TestFailed("exception message '" . $e->getMessage() . "' does not contain '$contains'");
        }
        return;
    }
    throw new TestFailed("expected an exception" . ($contains ? " containing '$contains'" : ""));
}
