<?php

declare(strict_types=1);

namespace Tarantool\Benchmarks;

use Tarantool\Benchmarks\CodeGenerator\Client as ClientCodeGenerator;
use Tarantool\Client\Client;

/**
 * @BeforeMethods({"setUp"})
 * @Revs(10000)
 * @Iterations(3)
 * @OutputMode("throughput")
 * @OutputTimeUnit("seconds")
 * @Executor("template")
 */
final class ClientHandlerBench
{
    public function setUp() : void
    {
        Client::fromDefaults()->call('create_fixtures');
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
            '$handler->handle(new \Tarantool\Client\Request\CallRequest("math.min", [42, mt_rand(1, 99)]));',
        ];
    }

    /**
     * @Subject
     */
    public function evaluate() : array
    {
        return [
            self::generateHandler('handler'),
            '$handler->handle(new \Tarantool\Client\Request\EvaluateRequest("return ...", [mt_rand()]));',
        ];
    }

    /**
     * @Subject
     */
    public function select() : array
    {
        return [
            self::generateHandler('handler'),
            '$handler->handle(new \Tarantool\Client\Request\SelectRequest(555, 0, [mt_rand(1, 100000)], 0, \PHP_INT_MAX & 0xffffffff, \Tarantool\Client\Schema\IteratorTypes::EQ));',
        ];
    }

    /**
     * @Subject
     */
    public function insert() : array
    {
        return [
            self::generateHandler('handler'),
            '$handler->handle(new \Tarantool\Client\Request\InsertRequest(555, [null, "foobar_".mt_rand()]));',
        ];
    }

    /**
     * @Subject
     */
    public function replace() : array
    {
        return [
            self::generateHandler('handler'),
            '$handler->handle(new \Tarantool\Client\Request\ReplaceRequest(555, [42, "foobar_".mt_rand()]));',
        ];
    }

    /**
     * @Subject
     */
    public function update() : array
    {
        return [
            self::generateHandler('handler'),
            '$handler->handle(new \Tarantool\Client\Request\UpdateRequest(555, 0, [42], [["=", 1, "foobar_".mt_rand()]]));',
        ];
    }

    /**
     * @Subject
     */
    public function upsert() : array
    {
        return [
            self::generateHandler('handler'),
            '$handler->handle(new \Tarantool\Client\Request\UpsertRequest(
                555, 
                [42, "foobar_".mt_rand()], 
                [["=", 1, "bazqux_".mt_rand()]]
            ));',
        ];
    }

    /**
     * @Subject
     */
    public function delete() : array
    {
        return [
            self::generateHandler('handler'),
            '$handler->handle(new \Tarantool\Client\Request\DeleteRequest(555, 0, [mt_rand(1, 100000)]));',
        ];
    }

    private static function generateHandler(string $instanceName) : string
    {
        return ClientCodeGenerator::generateHandler(
            $instanceName,
            $_SERVER['TNT_BENCH_PACKER_TYPE'] ?? ClientCodeGenerator::PACKER_TYPE_PURE
        );
    }
}
