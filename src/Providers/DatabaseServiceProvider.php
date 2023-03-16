<?php

namespace Netflex\Database\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Database\DatabaseManager;

use Netflex\Database\Driver\Connection;
use Netflex\Database\Adapters\Repository;
use Netflex\Database\Facades\Adapter;

use Netflex\Database\Adapters\CustomerAdapter;
use Netflex\Database\Adapters\EntryAdapter;
use Netflex\Database\Adapters\PageAdapter;

class DatabaseServiceProvider extends ServiceProvider
{
    protected $adapters = [
        'customer' => CustomerAdapter::class,
        'entry' => EntryAdapter::class,
        'page' => PageAdapter::class,
    ];

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

        foreach ($this->adapters as $name => $adapter) {
            Adapter::register($adapter)->withAlias($name);
        }
    }
}
