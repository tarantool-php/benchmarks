<?php

declare(strict_types=1);

namespace Tarantool\Benchmarks;

final class Config
{
    public const SPACE_ID = 555;
    public const SPACE_NAME = 'items';
    public const ROW_COUNT = 10000;

    public static function all() : array
    {
        return [
            'space_id' => self::SPACE_ID,
            'space_name' => self::SPACE_NAME,
            'row_count' => self::ROW_COUNT,
        ];
    }
}
