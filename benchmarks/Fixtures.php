<?php

declare(strict_types=1);

namespace Tarantool\Benchmarks;

use Tarantool\Client\Client;

trait Fixtures
{
    public function resetSchema() : void
    {
        Client::fromDefaults()->evaluate('create_space(...)', Config::all());
    }

    public function loadFixtures() : void
    {
        Client::fromDefaults()->call('load_fixtures', Config::all());
    }
}
