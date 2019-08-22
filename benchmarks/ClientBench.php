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
final class ClientBench
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
            '$result = $client->call("math.min", 42, mt_rand(1, 99));',
        ];
    }

    /**
     * @Subject
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
     */
    public function select() : array
    {
        return [
            self::generateSpace('space', Config::SPACE_ID),
            sprintf('$result = $space->select(\Tarantool\Client\Schema\Criteria::key([mt_rand(1, %d)]));', Config::ROW_COUNT),
        ];
    }

    /**
     * @Subject
     */
    public function insert() : array
    {
        return [
            self::generateSpace('space', Config::SPACE_ID),
            '$space->insert([null, "foobar_".mt_rand()]);',
        ];
    }

    /**
     * @Subject
     */
    public function replace() : array
    {
        return [
            self::generateSpace('space', Config::SPACE_ID),
            sprintf('$space->replace([mt_rand(1, %d), "a"]);', Config::ROW_COUNT),
        ];
    }

    /**
     * @Subject
     */
    public function update() : array
    {
        return [
            self::generateSpace('space', Config::SPACE_ID),
            sprintf('$space->update([mt_rand(1, %d)], \Tarantool\Client\Schema\Operations::set(1, "a"));', Config::ROW_COUNT),
        ];
    }

    /**
     * @Subject
     */
    public function upsert() : array
    {
        return [
            self::generateSpace('space', Config::SPACE_ID),
            sprintf('$space->upsert([mt_rand(1, %d), "a"], \Tarantool\Client\Schema\Operations::set(1, "b"));', Config::ROW_COUNT),
        ];
    }

    /**
     * @Subject
     */
    public function delete() : array
    {
        return [
            self::generateSpace('space', Config::SPACE_ID),
            sprintf('$space->delete([mt_rand(1, %d)]);', Config::ROW_COUNT),
        ];
    }

    private static function generateClient(string $variableName) : string
    {
        return ClientCodeGenerator::generateClient(
            $variableName,
            $_SERVER['TNT_BENCH_PACKER_TYPE'] ?? ClientCodeGenerator::PACKER_TYPE_PURE
        );
    }

    private static function generateSpace(string $variableName, int $spaceId) : string
    {
        return ClientCodeGenerator::generateSpace(
            $variableName,
            $spaceId,
            $_SERVER['TNT_BENCH_PACKER_TYPE'] ?? ClientCodeGenerator::PACKER_TYPE_PURE
        );
    }
}
