<?php

namespace Netflex\Database\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Database\DatabaseManager;

use Netflex\Database\Driver\Connection;
use Netflex\Database\Adapters\EntryAdapter;

use Netflex\DBAL\Adapters\ReadOnlyAdapter;

class DatabaseServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->app->resolving('db', function (DatabaseManager $db) {
            $db->extend('netflex', function ($config, $name) {
                $config['name'] = $name;
                return new Connection($config);
            });
        });

        $this->app->singleton('db.netflex.adapters.default', fn () => new ReadOnlyAdapter);
        $this->app->singleton('db.netflex.adapters.entry', fn () => new EntryAdapter);
    }
}
