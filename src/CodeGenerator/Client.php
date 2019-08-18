<?php

declare(strict_types=1);

namespace Tarantool\Benchmarks\CodeGenerator;

final class Client
{
    public const PACKER_TYPE_PECL = 'pecl';
    public const PACKER_TYPE_PURE = 'pure';

    public static function generateClient(string $instanceName, string $packerType = self::PACKER_TYPE_PURE) : string
    {
        return sprintf('$%s = %s;', $instanceName, self::generateClientRaw($packerType));
    }

    public static function generateSpace(string $instanceName, int $spaceId, string $packerType = self::PACKER_TYPE_PURE) : string
    {
        return sprintf('$%s = (%s)->getSpaceById(%d);', $instanceName, self::generateClientRaw($packerType), $spaceId);
    }

    public static function generateHandler(string $instanceName, string $packerType = self::PACKER_TYPE_PURE) : string
    {
        return sprintf('$%s = %s;', $instanceName, self::generateHandlerRaw($packerType));
    }

    private static function generateClientRaw(string $packerType) : string
    {
        return sprintf('new \Tarantool\Client\Client(%s)', self::generateHandlerRaw($packerType));
    }

    private static function generateHandlerRaw(string $packerType) : string
    {
        return sprintf('
            new \Tarantool\Client\Handler\DefaultHandler(
                \Tarantool\Client\Connection\StreamConnection::createTcp(),
                new \Tarantool\Client\Packer\%sPacker()
            )
        ', ucfirst(strtolower($packerType)));
    }
}
