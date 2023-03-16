<?php

namespace Netflex\Database\Facades;

use Illuminate\Support\Facades\Facade;

use Netflex\Database\Adapters\Repository;
use Netflex\Database\DBAL\Contracts\Connection;
use Netflex\Database\DBAL\Contracts\DatabaseAdapter;

/**
 * @method static bool alias(string $alias, string $target)
 * @method static Repository register(string $adapter, string|null $alias = null)
 * @method static DatabaseAdapter resolve(string $name, Connection $connection)
 * @see Repository
 */
class Adapter extends Facade
{
    protected static function getFacadeAccessor()
    {
        return 'db.netflex.adapters';
    }
}
