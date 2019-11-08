<?php

declare(strict_types=1);

namespace Tarantool\Benchmarks;

final class ClientHandlerBench extends Bench
{
    /**
     * @Subject
     * @Warmup(1)
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
     * @Warmup(1)
     */
    public function call() : array
    {
        return [
            self::generateHandler('handler'),
            '$response = $handler->handle(new \Tarantool\Client\Request\CallRequest("math.min", [42, \mt_rand(1, 99)]));',
        ];
    }

    /**
     * @Subject
     * @Warmup(1)
     */
    public function evaluate() : array
    {
        return [
            self::generateHandler('handler'),
            '$response = $handler->handle(new \Tarantool\Client\Request\EvaluateRequest("return ...", [\mt_rand()]));',
        ];
    }

    /**
     * @Subject
     * @Warmup(1)
     */
    public function select() : array
    {
        $this->resetSchema();
        $this->loadFixtures();

        return [
            self::generateHandler('handler'),
            sprintf('$response = $handler->handle(
                new \Tarantool\Client\Request\SelectRequest(%d, 0, [\mt_rand(1, %d)], 0, \PHP_INT_MAX & 0xffffffff, \Tarantool\Client\Schema\IteratorTypes::EQ)
            );', Config::SPACE_ID, Config::ROW_COUNT),
        ];
    }

    /**
     * @Subject
     * @Warmup(1)
     */
    public function insert() : array
    {
        $this->resetSchema();

        return [
            self::generateHandler('handler'),
            sprintf('$handler->handle(
                new \Tarantool\Client\Request\InsertRequest(%d, [null, "foobar_".\mt_rand()])
            );', Config::SPACE_ID),
        ];
    }

    /**
     * @Subject
     * @Warmup(1)
     */
    public function replace() : array
    {
        $this->resetSchema();
        $this->loadFixtures();

        return [
            self::generateHandler('handler'),
            sprintf('$handler->handle(
                new \Tarantool\Client\Request\ReplaceRequest(%d, [\mt_rand(1, %d), "a"])
            );', Config::SPACE_ID, Config::ROW_COUNT),
        ];
    }

    /**
     * @Subject
     * @Warmup(1)
     */
    public function update() : array
    {
        $this->resetSchema();
        $this->loadFixtures();

        return [
            self::generateHandler('handler'),
            sprintf('$handler->handle(
                new \Tarantool\Client\Request\UpdateRequest(%d, 0, [\mt_rand(1, %d)], [["=", 1, "a"]])
            );', Config::SPACE_ID, Config::ROW_COUNT),
        ];
    }

    /**
     * @Subject
     * @Warmup(1)
     */
    public function upsert() : array
    {
        $this->resetSchema();
        $this->loadFixtures();

        return [
            self::generateHandler('handler'),
            sprintf('$handler->handle(
                new \Tarantool\Client\Request\UpsertRequest(%d, [\mt_rand(1, %d), "a"], [["=", 1, "b"]])
            );', Config::SPACE_ID, Config::ROW_COUNT),
        ];
    }

    /**
     * @Subject
     * @Warmup(1)
     */
    public function delete() : array
    {
        $this->resetSchema();
        $this->loadFixtures();

        return [
            self::generateHandler('handler'),
            sprintf('$handler->handle(
                new \Tarantool\Client\Request\DeleteRequest(%d, 0, [\mt_rand(1, %d)])
            );', Config::SPACE_ID, Config::ROW_COUNT),
        ];
    }
}
