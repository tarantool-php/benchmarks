<?php

declare(strict_types=1);

namespace Tarantool\Benchmarks;

use Tarantool\Benchmarks\CodeGenerator\Tarantool as TarantoolCodeGenerator;

/**
 * @BeforeMethods({"setUp"})
 * @Revs(10000)
 * @Iterations(3)
 * @OutputMode("throughput")
 * @OutputTimeUnit("seconds")
 * @Executor("template")
 */
final class TarantoolBench {
    public function setUp() : void
    {
        (new \Tarantool())->call('create_fixtures');
    }

    /**
     * @Subject
     */
    public function ping() : array
    {
        return [
            self::generateClient('client'),
            '$client->ping();',
        ];
    }

    /**
     * @Subject
     */
    public function call() : array
    {
        return [
            self::generateClient('client'),
            '$client->call("math.min", [42, mt_rand(1, 99)]);',
        ];
    }

    /**
     * @Subject
     */
    public function evaluate() : array
    {
        return [
            self::generateClient('client'),
            '$client->evaluate("return ...", [mt_rand()]);',
        ];
    }

    /**
     * @Subject
     */
    public function select() : array
    {
        return [
            self::generateClient('client'),
            '$client->select(555, [mt_rand(1, 100000)]);',
        ];
    }

    /**
     * @Subject
     */
    public function insert() : array
    {
        return [
            self::generateClient('client'),
            '$client->insert(555, [null, "foobar_".mt_rand()]);',
        ];
    }

    /**
     * @Subject
     */
    public function replace() : array
    {
        return [
            self::generateClient('client'),
            '$client->replace(555, [42, "foobar_".mt_rand()]);',
        ];
    }

    /**
     * @Subject
     */
    public function update() : array
    {
        return [
            self::generateClient('client'),
            '$client->update(555, [42], [["field" => 1, "op" => "=", "arg" => "foobar_".mt_rand()]]);',
        ];
    }

    /**
     * @Subject
     */
    public function upsert() : array
    {
        return [
            self::generateClient('client'),
            '$client->upsert(555, [42, "foobar_".mt_rand()], [["field" => 1, "op" => "=", "arg" => "bazqux_".mt_rand()]]);',
        ];
    }

    /**
     * @Subject
     */
    public function delete() : array
    {
        return [
            self::generateClient('client'),
            '$client->delete(555, [mt_rand(1, 100000)]);',
        ];
    }


    private static function generateClient(string $instanceName) : string
    {
        return TarantoolCodeGenerator::generateClient($instanceName);
    }
}
