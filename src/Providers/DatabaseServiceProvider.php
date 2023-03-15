<?php

namespace Netflex\Database\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Database\DatabaseManager;

use Netflex\Database\Driver\Connection;
use Netflex\Database\Adapters\Repository;

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

        $this->app->singleton('db.netflex.adapters', function () {
            return new Repository;
        });
    }
}
