<?php

declare(strict_types=1);

namespace Tarantool\Benchmarks\CodeGenerator;

final class Tarantool
{
    public static function generateClient(string $instanceName) : string
    {
        return sprintf('$%s = new \Tarantool();', $instanceName);
    }
}
