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
final class ClientSqlBench
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
    public function select() : array
    {
        return [
            self::generateClient('client'),
            '$result = $client->executeQuery("SELECT * FROM \"items\" WHERE \"id\" = ?", mt_rand(1, 100000));',
        ];
    }

    /**
     * @Subject
     */
    public function insert() : array
    {
        return [
            self::generateClient('client'),
            '$client->executeUpdate("INSERT INTO \"items\" VALUES (null, ?)", "foobar_".mt_rand());',
        ];
    }

    /**
     * @Subject
     */
    public function update() : array
    {
        return [
            self::generateClient('client'),
            '$client->executeUpdate("UPDATE \"items\" SET \"name\" = ? WHERE \"id\" = ?", "", mt_rand(1, 100000));',
        ];
    }

    /**
     * @Subject
     */
    public function delete() : array
    {
        return [
            self::generateClient('client'),
            '$client->executeUpdate("DELETE FROM \"items\" WHERE \"id\" = ?", mt_rand(1, 100000));',
        ];
    }

    private static function generateClient(string $instanceName) : string
    {
        return ClientCodeGenerator::generateClient(
            $instanceName,
            $_SERVER['TNT_BENCH_PACKER_TYPE'] ?? ClientCodeGenerator::PACKER_TYPE_PURE
        );
    }
}
