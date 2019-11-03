<?php

declare(strict_types=1);

namespace Tarantool\Benchmarks;

final class ClientSqlBench extends Bench
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
    public function select() : array
    {
        self::resetSchema();
        self::loadFixtures();

        return [
            self::generateClient('client'),
            sprintf('$result = $client->executeQuery("SELECT * FROM \"%s\" WHERE \"id\" = ?", \mt_rand(1, %d));', Config::SPACE_NAME, Config::ROW_COUNT),
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
            self::generateClient('client'),
            sprintf('$client->executeUpdate("INSERT INTO \"%s\" VALUES (null, ?)", "foobar_".\mt_rand());', Config::SPACE_NAME),
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
            self::generateClient('client'),
            sprintf('$client->executeUpdate("UPDATE \"%s\" SET \"name\" = ? WHERE \"id\" = ?", "", \mt_rand(1, %d));', Config::SPACE_NAME, Config::ROW_COUNT),
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
            self::generateClient('client'),
            sprintf('$client->executeUpdate("DELETE FROM \"%s\" WHERE \"id\" = ?", \mt_rand(1, %d));', Config::SPACE_NAME, Config::ROW_COUNT),
        ];
    }
}
