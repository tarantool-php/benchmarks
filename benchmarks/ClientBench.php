<?php

declare(strict_types=1);

namespace Tarantool\Benchmarks;

final class ClientBench extends Bench
{
    /**
     * @Subject
     * @Warmup(1)
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
     * @Warmup(1)
     */
    public function call() : array
    {
        return [
            self::generateClient('client'),
            '$result = $client->call("math.min", 42, \mt_rand(1, 99));',
        ];
    }

    /**
     * @Subject
     * @Warmup(1)
     */
    public function evaluate() : array
    {
        return [
            self::generateClient('client'),
            '$result = $client->evaluate("return ...", \mt_rand());',
        ];
    }

    /**
     * @Subject
     * @Warmup(1)
     */
    public function select() : array
    {
        self::resetSchema();
        self::loadFixtures();

        return [
            self::generateSpace('space', Config::SPACE_ID),
            sprintf('$result = $space->select(\Tarantool\Client\Schema\Criteria::key([\mt_rand(1, %d)]));', Config::ROW_COUNT),
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
            self::generateSpace('space', Config::SPACE_ID),
            '$space->insert([null, "foobar_".\mt_rand()]);',
        ];
    }

    /**
     * @Subject
     * @Warmup(1)
     */
    public function replace() : array
    {
        self::resetSchema();
        self::loadFixtures();

        return [
            self::generateSpace('space', Config::SPACE_ID),
            sprintf('$space->replace([\mt_rand(1, %d), "a"]);', Config::ROW_COUNT),
        ];
    }

    /**
     * @Subject
     * @Warmup(1)
     */
    public function update() : array
    {
        self::resetSchema();
        self::loadFixtures();

        return [
            self::generateSpace('space', Config::SPACE_ID),
            sprintf('$space->update([\mt_rand(1, %d)], \Tarantool\Client\Schema\Operations::set(1, "a"));', Config::ROW_COUNT),
        ];
    }

    /**
     * @Subject
     * @Warmup(1)
     */
    public function upsert() : array
    {
        self::resetSchema();
        self::loadFixtures();

        return [
            self::generateSpace('space', Config::SPACE_ID),
            sprintf('$space->upsert([\mt_rand(1, %d), "a"], \Tarantool\Client\Schema\Operations::set(1, "b"));', Config::ROW_COUNT),
        ];
    }

    /**
     * @Subject
     * @Warmup(1)
     */
    public function delete() : array
    {
        self::resetSchema();
        self::loadFixtures();

        return [
            self::generateSpace('space', Config::SPACE_ID),
            sprintf('$space->delete([\mt_rand(1, %d)]);', Config::ROW_COUNT),
        ];
    }
}
