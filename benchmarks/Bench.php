<?php

declare(strict_types=1);

namespace Tarantool\Benchmarks;

use Tarantool\Benchmarks\CodeGenerator\Client as ClientCodeGenerator;
use Tarantool\Benchmarks\CodeGenerator\Tarantool as TarantoolCodeGenerator;
use Tarantool\Client\Client;

/**
 * @Revs(10000)
 * @Iterations(5)
 * @Sleep(1000000)
 * @OutputMode("throughput")
 * @OutputTimeUnit("seconds")
 * @Executor("template")
 */
abstract class Bench
{
    final protected static function resetSchema() : void
    {
        Client::fromOptions(['uri' => self::getEnv('TNT_BENCH_TARANTOOL_URI')])
            ->evaluate('create_space(...)', Config::all());
    }

    final protected static function loadFixtures() : void
    {
        Client::fromOptions(['uri' => self::getEnv('TNT_BENCH_TARANTOOL_URI')])
            ->call('load_fixtures', Config::all());
    }

    final protected static function generateHandler(string $variableName) : string
    {
        return ClientCodeGenerator::generateHandler(
            $variableName,
            self::getEnv('TNT_BENCH_TARANTOOL_URI'),
            self::getEnv('TNT_BENCH_PACKER')
        );
    }

    final protected static function generateClient(string $variableName) : string
    {
        return ClientCodeGenerator::generateClient(
            $variableName,
            self::getEnv('TNT_BENCH_TARANTOOL_URI'),
            self::getEnv('TNT_BENCH_PACKER')
        );
    }

    final protected static function generateSpace(string $variableName, int $spaceId) : string
    {
        return ClientCodeGenerator::generateSpace(
            $variableName,
            $spaceId,
            self::getEnv('TNT_BENCH_TARANTOOL_URI'),
            self::getEnv('TNT_BENCH_PACKER')
        );
    }

    final protected static function generateTarantool(string $variableName) : string
    {
        return TarantoolCodeGenerator::generateClient(
            $variableName,
            self::getEnv('TNT_BENCH_TARANTOOL_URI')
        );
    }

    final protected static function getEnv(string $name) : string
    {
        if (array_key_exists($name, $_SERVER)) {
            return $_SERVER[$name];
        }

        throw new \Error(sprintf('Environment variable "%s" is not defined.', $name));
    }
}
