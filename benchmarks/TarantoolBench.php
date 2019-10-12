<?php

declare(strict_types=1);

namespace Tarantool\Benchmarks;

use Tarantool\Benchmarks\CodeGenerator\Tarantool as TarantoolCodeGenerator;

/**
 * @Revs(10000)
 * @Iterations(5)
 * @Sleep(1000000)
 * @OutputMode("throughput")
 * @OutputTimeUnit("seconds")
 * @Executor("template")
 */
final class TarantoolBench
{
    use Fixtures;

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
        $this->loadFixtures();

        return [
            self::generateClient('client'),
            sprintf('$result = $client->select(%d, [mt_rand(1, %d)]);', Config::SPACE_ID, Config::ROW_COUNT),
        ];
    }

    /**
     * @Subject
     */
    public function insert() : array
    {
        $this->resetSchema();

        return [
            self::generateClient('client'),
            sprintf('$client->insert(%d, [null, "foobar_".mt_rand()]);', Config::SPACE_ID),
        ];
    }

    /**
     * @Subject
     */
    public function replace() : array
    {
        $this->loadFixtures();

        return [
            self::generateClient('client'),
            sprintf('$client->replace(%d, [mt_rand(1, %d), "a"]);', Config::SPACE_ID, Config::ROW_COUNT),
        ];
    }

    /**
     * @Subject
     */
    public function update() : array
    {
        $this->loadFixtures();

        return [
            self::generateClient('client'),
            sprintf('$client->update(%d, [mt_rand(1, %d)], [["field" => 1, "op" => "=", "arg" => "a"]]);', Config::SPACE_ID, Config::ROW_COUNT),
        ];
    }

    /**
     * @Subject
     */
    public function upsert() : array
    {
        $this->loadFixtures();

        return [
            self::generateClient('client'),
            sprintf('$client->upsert(%d, [mt_rand(1, %d), "a"], [["field" => 1, "op" => "=", "arg" => "b"]]);', Config::SPACE_ID, Config::ROW_COUNT),
        ];
    }

    /**
     * @Subject
     */
    public function delete() : array
    {
        $this->loadFixtures();

        return [
            self::generateClient('client'),
            sprintf('$client->delete(%d, [mt_rand(1, %d)]);', Config::SPACE_ID, Config::ROW_COUNT),
        ];
    }

    private static function generateClient(string $variableName) : string
    {
        return TarantoolCodeGenerator::generateClient($variableName);
    }
}
