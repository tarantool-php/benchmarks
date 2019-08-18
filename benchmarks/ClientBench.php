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
final class ClientBench
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
            '$client->call("math.min", 42, mt_rand(1, 99));',
        ];
    }

    /**
     * @Subject
     */
    public function evaluate() : array
    {
        return [
            self::generateClient('client'),
            '$client->evaluate("return ...", mt_rand());',
        ];
    }

    /**
     * @Subject
     */
    public function select() : array
    {
        return [
            self::generateSpace('space', 555),
            '$space->select(\Tarantool\Client\Schema\Criteria::key([mt_rand(1, 100000)]));',
        ];
    }

    /**
     * @Subject
     */
    public function insert() : array
    {
        return [
            self::generateSpace('space', 555),
            '$space->insert([null, "foobar_".mt_rand()]);',
        ];
    }

    /**
     * @Subject
     */
    public function replace() : array
    {
        return [
            self::generateSpace('space', 555),
            '$space->replace([42, "foobar_".mt_rand()]);',
        ];
    }

    /**
     * @Subject
     */
    public function update() : array
    {
        return [
            self::generateSpace('space', 555),
            '$space->update([42], \Tarantool\Client\Schema\Operations::set(1, "foobar_".mt_rand()));',
        ];
    }

    /**
     * @Subject
     */
    public function upsert() : array
    {
        return [
            self::generateSpace('space', 555),
            '$space->upsert([42, "foobar_".mt_rand()], \Tarantool\Client\Schema\Operations::set(1, "bazqux_".mt_rand()));',
        ];
    }

    /**
     * @Subject
     */
    public function delete() : array
    {
        return [
            self::generateSpace('space', 555),
            '$space->delete([mt_rand(1, 100000)]);',
        ];
    }

    private static function generateClient(string $instanceName) : string
    {
        return ClientCodeGenerator::generateClient(
            $instanceName,
            $_SERVER['TNT_BENCH_PACKER_TYPE'] ?? ClientCodeGenerator::PACKER_TYPE_PURE
        );
    }

    private static function generateSpace(string $instanceName, int $spaceId) : string
    {
        return ClientCodeGenerator::generateSpace(
            $instanceName,
            $spaceId,
            $_SERVER['TNT_BENCH_PACKER_TYPE'] ?? ClientCodeGenerator::PACKER_TYPE_PURE
        );
    }
}
