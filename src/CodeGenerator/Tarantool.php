<?php

declare(strict_types=1);

namespace Tarantool\Benchmarks\CodeGenerator;

final class Tarantool
{
    public static function generateClient(string $variableName, string $uri) : string
    {
        $parsed = parse_url($uri);
        if ('tcp' !== $parsed['scheme']) {
            throw new \InvalidArgumentException(sprintf('Invalid uri scheme "%s".', $parsed['scheme']));
        }

        return sprintf('$%s = new \Tarantool(\'%s\', %s);', $variableName, $parsed['host'], $parsed['port']);
    }
}
