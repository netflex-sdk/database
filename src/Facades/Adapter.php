<?php

namespace Netflex\Database\Facades;

use Illuminate\Support\Facades\Facade;

use Netflex\Database\DBAL\Contracts\Connection;
use Netflex\Database\DBAL\Contracts\DatabaseAdapter;

/**
 * @method static bool register(string $name, string $adapter)
 * @method static DatabaseAdapter resolve(string $name, Connection $connection)
 */
class Adapter extends Facade
{
    protected static function getFacadeAccessor()
    {
        return 'db.netflex.adapters';
    }
}
