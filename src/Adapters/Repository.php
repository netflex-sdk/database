<?php

namespace Netflex\Database\Adapters;

use RuntimeException;

use Illuminate\Support\Facades\App;
use Illuminate\Contracts\Container\BindingResolutionException;

use Netflex\Database\DBAL\Contracts\Connection;
use Netflex\Database\DBAL\Contracts\DatabaseAdapter;
use Netflex\Database\DBAL\Adapters\ReadOnlyAdapter;

final class Repository
{
    protected ?string $lastAdapter = null;

    protected array $adapters = [
        ReadOnlyAdapter::class,
    ];

    protected array $aliases = [
        'default' => ReadOnlyAdapter::class,
        'read-only' => ReadOnlyAdapter::class,
    ];

    /**
     * @param string $alias
     * @param string $target
     * @return bool
     */
    public function alias(string $alias, string $target)
    {
        if (array_key_exists($alias, $this->aliases)) {
            return false;
        }

        $this->aliases[$alias] = $target;

        return true;
    }

    public function withAlias(string $alias)
    {
        if ($this->lastAdapter !== null) {
            $adapter = $this->lastAdapter;
            $this->lastAdapter = null;

            return $this->alias($alias, $adapter);
        }

        throw new RuntimeException('No adapter has been registered');
    }

    /**
     * @param string $adapter
     * @param string|null $alias
     * @return Repistory
     */
    public function register(string $adapter, ?string $alias = null)
    {
        if (in_array($adapter, $this->adapters)) {
            throw new RuntimeException('Adapter [' . $adapter . '] is already registered');
        }

        $this->adapters[] = $adapter;
        $this->lastAdapter = $adapter;

        if ($alias !== null) {
            $this->withAlias($alias);
        }

        return $this;
    }

    public function resolve(string $name, Connection $connection): DatabaseAdapter
    {
        $previous = null;

        if (array_key_exists($name, $this->aliases)) {
            $name = $this->aliases[$name];
        }

        try {
            if (class_exists($name) && is_subclass_of($name, DatabaseAdapter::class)) {
                return App::make($name, ['connection' => $connection]);
            }

            if (in_array($name, $this->adapters)) {
                if (class_exists($name) && is_subclass_of($name, DatabaseAdapter::class)) {
                    return App::make($name, ['connection' => $connection]);
                }
            }
        } catch (BindingResolutionException $e) {
            $previous = $e;
        }

        throw new RuntimeException('Unable to resolve adapter [' . $name . '] for connection [' . $connection->getName() . ']', 0, $previous);
    }
}
