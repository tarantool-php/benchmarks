<?php

declare(strict_types=1);

namespace Tarantool\Benchmarks;

use Tarantool\Benchmarks\CodeGenerator\Client as ClientCodeGenerator;

/**
 * @Revs(10000)
 * @Iterations(5)
 * @Sleep(1000000)
 * @OutputMode("throughput")
 * @OutputTimeUnit("seconds")
 * @Executor("template")
 */
final class ClientBench
{
    use Fixtures;

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
            '$result = $client->call("math.min", 42, mt_rand(1, 99));',
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
            '$result = $client->evaluate("return ...", mt_rand());',
        ];
    }

    /**
     * @Subject
     * @Warmup(1)
     */
    public function select() : array
    {
        $this->loadFixtures();

        return [
            self::generateSpace('space', Config::SPACE_ID),
            sprintf('$result = $space->select(\Tarantool\Client\Schema\Criteria::key([mt_rand(1, %d)]));', Config::ROW_COUNT),
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
            self::generateSpace('space', Config::SPACE_ID),
            '$space->insert([null, "foobar_".mt_rand()]);',
        ];
    }

    /**
     * @Subject
     * @Warmup(1)
     */
    public function replace() : array
    {
        $this->loadFixtures();

        return [
            self::generateSpace('space', Config::SPACE_ID),
            sprintf('$space->replace([mt_rand(1, %d), "a"]);', Config::ROW_COUNT),
        ];
    }

    /**
     * @Subject
     * @Warmup(1)
     */
    public function update() : array
    {
        $this->loadFixtures();

        return [
            self::generateSpace('space', Config::SPACE_ID),
            sprintf('$space->update([mt_rand(1, %d)], \Tarantool\Client\Schema\Operations::set(1, "a"));', Config::ROW_COUNT),
        ];
    }

    /**
     * @Subject
     * @Warmup(1)
     */
    public function upsert() : array
    {
        $this->loadFixtures();

        return [
            self::generateSpace('space', Config::SPACE_ID),
            sprintf('$space->upsert([mt_rand(1, %d), "a"], \Tarantool\Client\Schema\Operations::set(1, "b"));', Config::ROW_COUNT),
        ];
    }

    /**
     * @Subject
     * @Warmup(1)
     */
    public function delete() : array
    {
        $this->loadFixtures();

        return [
            self::generateSpace('space', Config::SPACE_ID),
            sprintf('$space->delete([mt_rand(1, %d)]);', Config::ROW_COUNT),
        ];
    }

    private static function generateClient(string $variableName) : string
    {
        return ClientCodeGenerator::generateClient(
            $variableName,
            $_SERVER['TNT_BENCH_PACKER'] ?? ClientCodeGenerator::PACKER_PURE
        );
    }

    private static function generateSpace(string $variableName, int $spaceId) : string
    {
        return ClientCodeGenerator::generateSpace(
            $variableName,
            $spaceId,
            $_SERVER['TNT_BENCH_PACKER']
        );
    }
}
