<?php

namespace Netflex\Database\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Database\DatabaseManager;

use Netflex\Database\Driver\Connection;


class DatabaseServiceProvider extends ServiceProvider
{
    protected $adapters = [
        'default' => \Netflex\Database\DBAL\Adapters\ReadOnlyAdapter::class,
        'entry' => \Netflex\Database\Adapters\EntryAdapter::class,
    ];

    public function register()
    {
        $this->app->resolving('db', function (DatabaseManager $db) {
            $db->extend('netflex', function ($config, $name) {
                $config['name'] = $name;
                return new Connection($config);
            });
        });

        foreach ($this->adapters as $name => $adapter) {
            $this->app->bind("db.netflex.adapters.{$name}", $adapter);
        }
    }
}
