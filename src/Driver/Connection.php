<?php

namespace Netflex\Database\Driver;

use Closure;
use Exception;
use RuntimeException;

use Illuminate\Database\QueryException;
use Illuminate\Database\Events\StatementPrepared;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Database\Connection as BaseConnection;

use PDOStatement;
use Netflex\Database\Driver\PDO;

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
     * Run a SQL statement.
     *
     * @param array $query
     * @param array $bindings
     * @param Closure  $callback
     * @return mixed
     *
     * @throws QueryException
     */
    protected function runQueryCallback($query, $bindings, Closure $callback)
    {
        // To execute the statement, we'll simply call the callback, which will actually
        // run the SQL against the PDO connection. Then we can calculate the time it
        // took to execute and log the query SQL, bindings and time in our memory.
        try {
            return $callback($query, $bindings);
        }

        // If an exception occurs when attempting to run a query, we'll format the error
        // message to include the bindings with SQL, which will make this exception a
        // lot more helpful to the developer instead of just the database's errors.
        catch (Exception $e) {
            throw new QueryException(
                json_encode($query),
                $this->prepareBindings($bindings),
                $e
            );
        }
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
        dd($query);
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
