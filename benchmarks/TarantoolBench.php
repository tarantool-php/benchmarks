<?php

declare(strict_types=1);

namespace Tarantool\Benchmarks;

final class TarantoolBench extends Bench
{
    /**
     * @Subject
     * @Warmup(1)
     */
    public function ping() : array
    {
        return [
            self::generateTarantool('client'),
            '$client->ping();',
        ];
    }

    /**
     * @Subject
     * @Warmup(1)
     */
    public function call() : array
    {
        return [
            self::generateTarantool('client'),
            '$result = $client->call("math.min", [42, mt_rand(1, 99)]);',
        ];
    }

    /**
     * @Subject
     * @Warmup(1)
     */
    public function evaluate() : array
    {
        return [
            self::generateTarantool('client'),
            '$result = $client->evaluate("return ...", [mt_rand()]);',
        ];
    }

    /**
     * @Subject
     * @Warmup(1)
     */
    public function select() : array
    {
        self::loadFixtures();

        return [
            self::generateTarantool('client'),
            sprintf('$result = $client->select(%d, [mt_rand(1, %d)]);', Config::SPACE_ID, Config::ROW_COUNT),
        ];
    }

    /**
     * @Subject
     * @Warmup(1)
     */
    public function insert() : array
    {
        self::resetSchema();

        return [
            self::generateTarantool('client'),
            sprintf('$client->insert(%d, [null, "foobar_".mt_rand()]);', Config::SPACE_ID),
        ];
    }

    /**
     * @Subject
     * @Warmup(1)
     */
    public function replace() : array
    {
        self::loadFixtures();

        return [
            self::generateTarantool('client'),
            sprintf('$client->replace(%d, [mt_rand(1, %d), "a"]);', Config::SPACE_ID, Config::ROW_COUNT),
        ];
    }

    /**
     * @Subject
     * @Warmup(1)
     */
    public function update() : array
    {
        self::loadFixtures();

        return [
            self::generateTarantool('client'),
            sprintf('$client->update(%d, [mt_rand(1, %d)], [["field" => 1, "op" => "=", "arg" => "a"]]);', Config::SPACE_ID, Config::ROW_COUNT),
        ];
    }

    /**
     * @Subject
     * @Warmup(1)
     */
    public function upsert() : array
    {
        self::loadFixtures();

        return [
            self::generateTarantool('client'),
            sprintf('$client->upsert(%d, [mt_rand(1, %d), "a"], [["field" => 1, "op" => "=", "arg" => "b"]]);', Config::SPACE_ID, Config::ROW_COUNT),
        ];
    }

    /**
     * @Subject
     * @Warmup(1)
     */
    public function delete() : array
    {
        self::loadFixtures();

        return [
            self::generateTarantool('client'),
            sprintf('$client->delete(%d, [mt_rand(1, %d)]);', Config::SPACE_ID, Config::ROW_COUNT),
        ];
    }
}
