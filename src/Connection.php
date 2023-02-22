<?php

namespace Netflex\Database;

use Illuminate\Database\Connection as BaseConnection;
use Netflex\Database\PDO;
use RuntimeException;

class Connection extends BaseConnection
{
    protected string $name;
    protected string $connection;

    public function __construct(array $config)
    {
        $pdo = new PDO(['connection' => $config['connection'] ?? 'default']);
        parent::__construct($pdo, '', $config['prefix'] ?? '', $config);
        $this->setTablePrefix($config['prefix'] ?? '');
        $this->name = $config['name'] ?? 'default';
        $this->connection = $config['connection'] ?? 'default';
    }

    /**
     * Run an insert statement against the database.
     *
     * @param  string  $query
     * @param  array  $bindings
     * @return bool
     */
    public function insert($query, $bindings = [])
    {
        throw new RuntimeException('This database engine does not support inserts.');
    }

    /**
     * Run an update statement against the database.
     *
     * @param  string  $query
     * @param  array  $bindings
     * @return int
     */
    public function update($query, $bindings = [])
    {
        throw new RuntimeException('This database engine does not support updates.');
    }

    /**
     * Run a delete statement against the database.
     *
     * @param  string  $query
     * @param  array  $bindings
     * @return int
     */
    public function delete($query, $bindings = [])
    {
        throw new RuntimeException('This database engine does not support deletes.');
    }

    /**
     * Get the default query grammar instance.
     *
     * @return \Illuminate\Database\Query\Grammars\Grammar
     */
    protected function getDefaultQueryGrammar()
    {
        return new QueryGrammar;
    }
}
