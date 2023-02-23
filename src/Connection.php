<?php

namespace Netflex\Database;

use RuntimeException;

use Illuminate\Database\Events\StatementPrepared;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Database\Connection as BaseConnection;

use PDOStatement;
use Netflex\Database\PDO;

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
     * Log a query in the connection's query log.
     *
     * @param array $query
     * @param array $bindings
     * @param float|null $time
     * @return void
     */
    public function logQuery($query, $bindings, $time = null)
    {
        $this->event(new QueryExecuted(json_encode($query), $bindings, $time, $this));

        if ($this->loggingQueries) {
            $this->queryLog[] = compact('query', 'bindings', 'time');
        }
    }

    /**
     * Configure the PDO prepared statement.
     *
     * @param PDOStatement $statement
     * @return PDOStatement
     */
    protected function prepared(PDOStatement $statement)
    {
        $statement->setFetchMode($this->fetchMode);

        $this->event(new StatementPrepared(
            $this,
            $statement
        ));

        return $statement;
    }

    /**
     * Run an insert statement against the database.
     *
     * @param string $query
     * @param array $bindings
     * @return bool
     */
    public function insert($query, $bindings = [])
    {
        throw new RuntimeException('This database engine does not support inserts.');
    }

    /**
     * Run an update statement against the database.
     *
     * @param string $query
     * @param array $bindings
     * @return int
     */
    public function update($query, $bindings = [])
    {
        throw new RuntimeException('This database engine does not support updates.');
    }

    /**
     * Run a delete statement against the database.
     *
     * @param string $query
     * @param array $bindings
     * @return int
     */
    public function delete($query, $bindings = [])
    {
        throw new RuntimeException('This database engine does not support deletes.');
    }

    /**
     * Get the default query grammar instance.
     *
     * @return QueryGrammar
     */
    protected function getDefaultQueryGrammar()
    {
        return new QueryGrammar;
    }
}
