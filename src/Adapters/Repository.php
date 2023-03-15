<?php

namespace Netflex\Database\Adapters;

use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Support\Facades\App;
use RuntimeException;

use Netflex\Database\DBAL\Contracts\Connection;
use Netflex\Database\DBAL\Contracts\DatabaseAdapter;

final class Repository
{
    protected array $adapters = [
        'default'  => \Netflex\Database\DBAL\Adapters\ReadOnlyAdapter::class,
        'entry'    => \Netflex\Database\Adapters\EntryAdapter::class,
        'customer' => \Netflex\Database\Adapters\CustomerAdapter::class,
        'page'     => \Netflex\Database\Adapters\PageAdapter::class,
    ];

    /**
     * @param string $name
     * @param string $adapter
     * @return bool
     */
    public function register(string $name, string $adapter)
    {
        if (array_key_exists($name, $this->adapters)) {
            return false;
        }

        $this->adapters[$name] = $adapter;

        return true;
    }

    public function resolve(string $name, Connection $connection): DatabaseAdapter
    {
        $previous = null;

        try {
            if (class_exists($name) && is_subclass_of($name, DatabaseAdapter::class)) {
                return App::make($name, ['connection' => $connection]);
            }

            if (array_key_exists($name, $this->adapters)) {
                $class = $this->adapters[$name];

                if (class_exists($class) && is_subclass_of($class, DatabaseAdapter::class)) {
                    return App::make($class, ['connection' => $connection]);
                }
            }
        } catch (BindingResolutionException $e) {
            $previous = $e;
        }

        throw new RuntimeException('Unable to resolve adapter [' . $name . '] for connection [' . $connection->getName() . ']', 0, $previous);
    }
}
