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
    public function select() : array
    {
        return [
            self::generateClient('client'),
            sprintf('$result = $client->executeQuery("SELECT * FROM \"%s\" WHERE \"id\" = ?", mt_rand(1, %d));', Config::SPACE_NAME, Config::ROW_COUNT),
        ];
    }

    /**
     * @Subject
     */
    public function insert() : array
    {
        return [
            self::generateClient('client'),
            sprintf('$client->executeUpdate("INSERT INTO \"%s\" VALUES (null, ?)", "foobar_".mt_rand());', Config::SPACE_NAME),
        ];
    }

    /**
     * @Subject
     */
    public function update() : array
    {
        return [
            self::generateClient('client'),
            sprintf('$client->executeUpdate("UPDATE \"%s\" SET \"name\" = ? WHERE \"id\" = ?", "", mt_rand(1, %d));', Config::SPACE_NAME, Config::ROW_COUNT),
        ];
    }

    /**
     * @Subject
     */
    public function delete() : array
    {
        return [
            self::generateClient('client'),
            sprintf('$client->executeUpdate("DELETE FROM \"%s\" WHERE \"id\" = ?", mt_rand(1, %d));', Config::SPACE_NAME, Config::ROW_COUNT),
        ];
    }

    private static function generateClient(string $variableName) : string
    {
        return ClientCodeGenerator::generateClient(
            $variableName,
            $_SERVER['TNT_BENCH_PACKER_TYPE'] ?? ClientCodeGenerator::PACKER_TYPE_PURE
        );
    }
}
