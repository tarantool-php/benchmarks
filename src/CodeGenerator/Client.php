<?php

declare(strict_types=1);

namespace Tarantool\Benchmarks\CodeGenerator;

final class Client
{
    public static function generateClient(string $variableName, string $uri, string $packer) : string
    {
        return sprintf('$%s = %s;', $variableName, self::generateClientRaw($uri, $packer));
    }

    public static function generateSpace(string $variableName, int $spaceId, string $uri, string $packer) : string
    {
        return sprintf('$%s = (%s)->getSpaceById(%d);', $variableName, self::generateClientRaw($uri, $packer), $spaceId);
    }

    public static function generateHandler(string $variableName, string $uri, string $packer) : string
    {
        return sprintf('$%s = %s;', $variableName, self::generateHandlerRaw($uri, $packer));
    }

    private static function generateClientRaw(string $uri, string $packer) : string
    {
        return sprintf('new \Tarantool\Client\Client(%s)', self::generateHandlerRaw($uri, $packer));
    }

    private static function generateHandlerRaw(string $uri, string $packer) : string
    {
        return sprintf('
            new \Tarantool\Client\Handler\DefaultHandler(
                \Tarantool\Client\Connection\StreamConnection::createTcp(\'%s\'),
                new \Tarantool\Client\Packer\%sPacker()
            )
        ', $uri, ucfirst($packer));
    }
}
