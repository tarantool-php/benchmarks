<?php

declare(strict_types=1);

namespace Tarantool\Benchmarks;

use Tarantool\Benchmarks\CodeGenerator\Tarantool as TarantoolCodeGenerator;

/**
 * @BeforeMethods({"setUp"})
 * @Revs(10000)
 * @Iterations(5)
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
            '$result = $client->call("math.min", [42, mt_rand(1, 99)]);',
        ];
    }

    /**
     * @Subject
     */
    public function evaluate() : array
    {
        return [
            self::generateClient('client'),
            '$result = $client->evaluate("return ...", [mt_rand()]);',
        ];
    }

    /**
     * @Subject
     */
    public function select() : array
    {
        return [
            self::generateClient('client'),
            '$result = $client->select(555, [mt_rand(1, 100000)]);',
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
            '$client->replace(555, [mt_rand(1, 100000), "a"]);',
        ];
    }

    /**
     * @Subject
     */
    public function update() : array
    {
        return [
            self::generateClient('client'),
            '$client->update(555, [mt_rand(1, 100000)], [["field" => 1, "op" => "=", "arg" => "a"]]);',
        ];
    }

    /**
     * @Subject
     */
    public function upsert() : array
    {
        return [
            self::generateClient('client'),
            '$client->upsert(555, [mt_rand(1, 100000), "a"], [["field" => 1, "op" => "=", "arg" => "b"]]);',
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
