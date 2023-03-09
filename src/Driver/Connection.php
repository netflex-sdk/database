<?php

namespace Netflex\Database\Driver;

use Closure;
use Exception;
use PDOStatement;
use RuntimeException;

use Illuminate\Database\Connection as BaseConnection;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Database\Events\StatementPrepared;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Str;
use Netflex\Database\Adapters\EntryAdapter;
use Netflex\Database\Contracts\DatabaseAdapter;
use Netflex\Database\Exceptions\QueryException;

use Netflex\Database\Driver\Doctrine\Driver as DoctrineDriver;
use Netflex\Database\Driver\PDO;
use Netflex\Database\Driver\QueryGrammar;
use Netflex\Database\Driver\Schema\SchemaGrammar;
use Netflex\Database\Driver\Schema\SchemaBuilder;

class Connection extends BaseConnection
{
    protected string $name;
    protected string $connection;
    protected ?string $adapter = null;

    const DB_ADAPTERS = [
        'entry' => \Netflex\Database\Adapters\EntryAdapter::class,
    ];

    public function __construct(array $config)
    {
        $pdo = new PDO($config);
        parent::__construct($pdo, '', $config['prefix'] ?? '', $config);

        $this->name = $config['name'] ?? 'default';
        $this->connection = $config['connection'] ?? 'default';

        $this->setTablePrefix($config['prefix'] ?? '');
        $this->setAdapter($config['adapter'] ?? null);
    }

    protected function setAdapter(?string $adapter = null)
    {
        if ($adapter === null) {
            return;
        }

        if (array_key_exists($adapter, static::DB_ADAPTERS)) {
            $adapter = static::DB_ADAPTERS[$adapter];
        }

        if ($adapter === EntryAdapter::class && !$this->getTablePrefix()) {
            $this->setTablePrefix('entry_');
        }

        if (!$adapter && $this->getTablePrefix() === 'entry_') {
            $adapter = EntryAdapter::class;
        }

        $this->adapter = $adapter;
    }

    protected function getAdapter(): DatabaseAdapter
    {
        if ($adapter = $this->adapter) {
            try {
                return App::make($adapter);
            } catch (Exception $e) {
                throw new RuntimeException('Invalid adapter [' . $adapter . '] for connection [' . $this->name . ']');
            }
        }

        throw new RuntimeException('No adapter specified for connection [' . $this->name . ']');
    }

    /**
     * Get the current PDO connection.
     *
     * @return PDO
     */
    public function getPdo()
    {
        /** @var PDO $pdo */
        $pdo = parent::getPdo();
        return $pdo;
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

    protected function performBulkAction($index, $data, $clause, Closure $callback)
    {
        $pdo = $this->getPdo();

        $clause['_source'] = ['id'];
        $clause['size'] = 10000;
        $clause['index'] = $index;

        $statement = $pdo->prepare([
            'command' => Command::SEARCH,
            'arguments' => $clause
        ]);

        $statement->execute();

        $results = collect($statement->fetchAll())->pluck('id');
        $affected = 0;

        foreach ($results as $id) {
            if ($callback([
                'index' => $index,
                'id' => $id,
                'data' => $data,
            ])) {
                $affected++;
            }
        }

        return $affected;
    }

    /**
     * Run an insert statement against the database.
     *
     * @param array $query
     * @param array $bindings
     * @return bool
     */
    public function insert($query, $bindings = [])
    {
        $index = $query['index'] ?? null;
        $data = $query['data'] ?? null;
        $payload = [];
        $table = $index;

        if (array_key_first($data) === 0) {
            foreach ($data as $item) {
                $this->insert(['index' => $index, 'data' => $item], $bindings);
            }

            return true;
        }

        if (Str::startsWith($table, $this->getTablePrefix())) {
            $table = Str::after($table, $this->getTablePrefix());
        }

        foreach ($data as $key => $value) {
            $payload[$this->queryGrammar->removeQualifiedColumn($table, $key)] = $value;
        }

        return $this->getAdapter()->insert($this->getPdo(), $payload, $table);
    }

    /**
     * Run an update statement against the database.
     *
     * @param array $query
     * @param array $bindings
     * @return int
     */
    public function update($query, $bindings = [])
    {
        $index = $query['index'] ?? null;
        $id = $query['id'] ?? false;
        $clause = $query['query'] ?? null;
        $data = $query['data'] ?? null;
        $payload = [];
        $table = $index;

        if (!$id && $clause) {
            return $this->performBulkAction($index, $data, $clause, function ($query) {
                return $this->update($query);
            });
        }

        if (Str::startsWith($table, $this->getTablePrefix())) {
            $table = Str::after($table, $this->getTablePrefix());
        }

        foreach ($data as $key => $value) {
            $payload[$this->queryGrammar->removeQualifiedColumn($table, $key)] = $value;
        }

        return $this->getAdapter()->update($this->getPdo(), (int) $id, $payload, $table);
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
        $index = $query['index'] ?? null;
        $id = $query['id'] ?? false;
        $clause = $query['query'] ?? null;
        $table = $index;

        if (!$id && $clause) {
            return $this->performBulkAction($index, [], $clause, function ($query) {
                return $this->delete($query);
            });
        }

        if (Str::startsWith($table, $this->getTablePrefix())) {
            $table = Str::after($table, $this->getTablePrefix());
        }

        return $this->getAdapter()->delete($this->getPdo(), (int) $id, $table);
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

    /**
     * Get the default schema grammar instance.
     *
     * @return QueryGrammar
     */
    protected function getDefaultSchemaGrammar()
    {
        return new SchemaGrammar;
    }

    public function getSchemaBuilder()
    {
        return new SchemaBuilder($this);
    }

    public function getDoctrineDriver(): DoctrineDriver
    {
        return new DoctrineDriver($this);
    }
}
