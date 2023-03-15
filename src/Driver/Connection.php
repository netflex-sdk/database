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

use Netflex\Database\DBAL\PDO;
use Netflex\Database\DBAL\Command;
use Netflex\Database\DBAL\Contracts\DatabaseAdapter;
use Netflex\Database\DBAL\Exceptions\QueryException;
use Netflex\Database\DBAL\Contracts\Connection as ConnectionContract;

use Netflex\Database\Adapters\EntryAdapter;

use Netflex\Database\DBAL\Doctrine\Driver as DoctrineDriver;
use Netflex\Database\Driver\QueryGrammar;
use Netflex\Database\Driver\Schema\SchemaGrammar;
use Netflex\Database\Driver\Schema\SchemaBuilder;

class Connection extends BaseConnection implements ConnectionContract
{
    protected string $name;
    protected string $connection;
    protected ?string $adapter = null;
    protected ?DatabaseAdapter $resolvedAdapter = null;

    public function __construct(array $config)
    {
        $pdo = new PDO($config);
        parent::__construct($pdo, '', $config['prefix'] ?? '', $config);

        $this->name = $config['name'] ?? 'default';
        $this->connection = $config['connection'] ?? 'default';

        $this->setTablePrefix($config['prefix'] ?? '');
        $this->setAdapter($config['adapter'] ?? 'default');
        $pdo->setAdapter($this->getAdapter());
    }

    protected function setAdapter(?string $adapter = null)
    {
        if ($adapter === null) {
            return;
        }

        /** @phpstan-ignore-next-line */
        $adapter = App::bound('db.netflex.adapters.' . $adapter)
            ? ('db.netflex.adapters.' . $adapter)
            : $adapter;

        if ($adapter === EntryAdapter::class && !$this->getTablePrefix()) {
            $this->setTablePrefix('entry_');
        }

        if (!$adapter && $this->getTablePrefix() === 'entry_') {
            $adapter = EntryAdapter::class;
        }

        $this->adapter = $adapter;
    }

    public function getAdapter(): DatabaseAdapter
    {
        if ($this->resolvedAdapter !== null) {
            return $this->resolvedAdapter;
        }

        if ($adapter = $this->adapter) {
            try {
                $this->resolvedAdapter = App::make($adapter, ['connection' => $this]);
                return $this->resolvedAdapter;
            } catch (Exception $previous) {
                throw new RuntimeException(
                    'Invalid adapter [' . $adapter . '] for connection [' . $this->name . ']. (Exception: ' . $previous->getMessage() . ')',
                    $previous->getCode(),
                    $previous
                );
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
     * Get the default post processor instance.
     *
     * @return Processor
     */
    protected function getDefaultPostProcessor()
    {
        return new PostProcessor;
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
            throw QueryException::make(
                $this->name,
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
        $clause['table'] = $index;

        $statement = $pdo->prepare([
            'command' => Command::SELECT,
            'arguments' => $clause
        ]);

        $statement->execute();

        $results = collect($statement->fetchAll())->pluck('id');
        $affected = 0;

        foreach ($results as $id) {
            if ($callback([
                'table' => $index,
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
        $index = $query['table'] ?? null;
        $data = $query['data'] ?? null;
        $payload = [];
        $table = $index;

        if (array_key_first($data) === 0) {
            foreach ($data as $item) {
                $this->insert(['table' => $index, 'data' => $item], $bindings);
            }

            return true;
        }

        if (Str::startsWith($table, $this->getTablePrefix())) {
            $table = Str::after($table, $this->getTablePrefix());
        }

        foreach ($data as $key => $value) {
            $payload[$this->queryGrammar->removeQualifiedColumn($table, $key)] = $value;
        }

        $statement = $this->getPdo()
            ->prepare([
                'command' => Command::INSERT,
                'arguments' => [
                    'table' => $table,
                    'payload' => $payload,
                ]
            ]);

        return $statement->execute();
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
        $index = $query['table'] ?? null;
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

        $statement = $this->getPdo()
            ->prepare([
                'command' => Command::UPDATE,
                'arguments' => [
                    'id' => $id,
                    'table' => $table,
                    'payload' => $payload,
                ]
            ]);

        return $statement->execute();
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
        $index = $query['table'] ?? null;
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

        $statement = $this->getPdo()
            ->prepare([
                'command' => Command::DELETE,
                'arguments' => [
                    'id' => $id,
                    'table' => $table
                ]
            ]);

        return $statement->execute();
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
        return new SchemaGrammar($this);
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
