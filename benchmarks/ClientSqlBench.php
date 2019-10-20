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
final class ClientSqlBench
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
    public function select() : array
    {
        $this->loadFixtures();

        return [
            self::generateClient('client'),
            sprintf('$result = $client->executeQuery("SELECT * FROM \"%s\" WHERE \"id\" = ?", mt_rand(1, %d));', Config::SPACE_NAME, Config::ROW_COUNT),
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
            self::generateClient('client'),
            sprintf('$client->executeUpdate("INSERT INTO \"%s\" VALUES (null, ?)", "foobar_".mt_rand());', Config::SPACE_NAME),
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
            self::generateClient('client'),
            sprintf('$client->executeUpdate("UPDATE \"%s\" SET \"name\" = ? WHERE \"id\" = ?", "", mt_rand(1, %d));', Config::SPACE_NAME, Config::ROW_COUNT),
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
            self::generateClient('client'),
            sprintf('$client->executeUpdate("DELETE FROM \"%s\" WHERE \"id\" = ?", mt_rand(1, %d));', Config::SPACE_NAME, Config::ROW_COUNT),
        ];
    }

    private static function generateClient(string $variableName) : string
    {
        return ClientCodeGenerator::generateClient(
            $variableName,
            $_SERVER['TNT_BENCH_PACKER']
        );
    }
}
