<?php

declare(strict_types=1);

namespace Tarantool\Benchmarks;

use Tarantool\Benchmarks\CodeGenerator\Client as ClientCodeGenerator;
use Tarantool\Client\Client;

/**
 * @BeforeMethods({"setUp"})
 * @Revs(10000)
 * @Iterations(5)
 * @OutputMode("throughput")
 * @OutputTimeUnit("seconds")
 * @Executor("template")
 */
final class ClientHandlerBench
{
    public function setUp() : void
    {
        Client::fromDefaults()->call('create_fixtures', Config::all());
    }

    /**
     * @Subject
     */
    public function ping() : array
    {
        return [
            self::generateHandler('handler').
            '$request = new \Tarantool\Client\Request\PingRequest();',
            '$handler->handle($request);',
        ];
    }

    /**
     * @Subject
     */
    public function call() : array
    {
        return [
            self::generateHandler('handler'),
            '$response = $handler->handle(new \Tarantool\Client\Request\CallRequest("math.min", [42, mt_rand(1, 99)]));',
        ];
    }

    /**
     * @Subject
     */
    public function evaluate() : array
    {
        return [
            self::generateHandler('handler'),
            '$response = $handler->handle(new \Tarantool\Client\Request\EvaluateRequest("return ...", [mt_rand()]));',
        ];
    }

    /**
     * @Subject
     */
    public function select() : array
    {
        return [
            self::generateHandler('handler'),
            sprintf('$response = $handler->handle(
                new \Tarantool\Client\Request\SelectRequest(%d, 0, [mt_rand(1, %d)], 0, \PHP_INT_MAX & 0xffffffff, \Tarantool\Client\Schema\IteratorTypes::EQ)
            );', Config::SPACE_ID, Config::ROW_COUNT),
        ];
    }

    /**
     * @Subject
     */
    public function insert() : array
    {
        return [
            self::generateHandler('handler'),
            sprintf('$handler->handle(
                new \Tarantool\Client\Request\InsertRequest(%d, [null, "foobar_".mt_rand()])
            );', Config::SPACE_ID),
        ];
    }

    /**
     * @Subject
     */
    public function replace() : array
    {
        return [
            self::generateHandler('handler'),
            sprintf('$handler->handle(
                new \Tarantool\Client\Request\ReplaceRequest(%d, [mt_rand(1, %d), "a"])
            );', Config::SPACE_ID, Config::ROW_COUNT),
        ];
    }

    /**
     * @Subject
     */
    public function update() : array
    {
        return [
            self::generateHandler('handler'),
            sprintf('$handler->handle(
                new \Tarantool\Client\Request\UpdateRequest(%d, 0, [mt_rand(1, %d)], [["=", 1, "a"]])
            );', Config::SPACE_ID, Config::ROW_COUNT),
        ];
    }

    /**
     * @Subject
     */
    public function upsert() : array
    {
        return [
            self::generateHandler('handler'),
            sprintf('$handler->handle(
                new \Tarantool\Client\Request\UpsertRequest(%d, [mt_rand(1, %d), "a"], [["=", 1, "b"]])
            );', Config::SPACE_ID, Config::ROW_COUNT),
        ];
    }

    /**
     * @Subject
     */
    public function delete() : array
    {
        return [
            self::generateHandler('handler'),
            sprintf('$handler->handle(
                new \Tarantool\Client\Request\DeleteRequest(%d, 0, [mt_rand(1, %d)])
            );', Config::SPACE_ID, Config::ROW_COUNT),
        ];
    }

    private static function generateHandler(string $variableName) : string
    {
        return ClientCodeGenerator::generateHandler(
            $variableName,
            $_SERVER['TNT_BENCH_PACKER_TYPE'] ?? ClientCodeGenerator::PACKER_TYPE_PURE
        );
    }
}
