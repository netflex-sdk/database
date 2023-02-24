<?php

namespace Netflex\Database\Driver;

use Closure;
use Exception;
use PDOStatement;
use RuntimeException;

use Illuminate\Database\Connection as BaseConnection;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Database\Events\StatementPrepared;
use Illuminate\Support\Str;

use Netflex\Database\Driver\PDO;
use Netflex\Database\Driver\QueryGrammar;
use Netflex\Database\Driver\Schema\Field;
use Netflex\Database\Driver\Schema\SchemaGrammar;
use Netflex\Database\Driver\Schema\SchemaBuilder;
use Netflex\Database\Driver\Schema\Structure;
use Netflex\Database\Exceptions\QueryException;

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
        $type = $index;

        if (array_key_first($data) === 0) {
            foreach ($data as $item) {
                $this->insert(['index' => $index, 'data' => $item], $bindings);
            }

            return true;
        }

        if (Str::startsWith($table, 'entry_')) {
            $table = Str::after($table, 'entry_');
            $type = 'entry';
        }

        foreach ($data as $key => $value) {
            $payload[$this->queryGrammar->removeQualifiedColumn($table, $key)] = $value;
        }

        switch ($type) {
            case 'entry':
                return $this->insertEntry($table, $payload);
            case 'customer':
                return $this->insertCustomer($payload);
            default:
                break;
        }

        throw new RuntimeException('This database engine does not support inserts for [' . $index . '].');
    }

    /**
     * @param string|int $structure
     * @param array $data
     * @return bool
     */
    protected function insertEntry($structure, $data)
    {
        if (!isset($data['name'])) {
            $data['name'] = (string) Str::uuid();
        }

        if (!isset($data['revision_publish'])) {
            $data['revision_publish'] = true;
        }

        $pdo = $this->getPdo();
        $pdo->setLastInsertId(null);
        $client = $pdo->getApiClient();
        $result = $client->post('builder/structures/' . $structure . '/entry', $data);
        $pdo->setLastInsertId($result->entry_id);
        return true;
    }

    protected function insertCustomer($data)
    {
        $pdo = $this->getPdo();
        $pdo->setLastInsertId(null);
        $client = $pdo->getApiClient();
        $result = $client->post('relations/customers/customer', $data);
        $pdo->setLastInsertId($result->customer_id);
        return true;
    }

    protected function bulk($index, $data, $clause, Closure $callback)
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
            $callback([
                'index' => $index,
                'id' => $id,
                'data' => $data,
            ]);

            $affected++;
        }

        return $affected;
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
        $type = $index;

        if (!$id && $clause) {
            return $this->bulk($index, $data, $clause, function ($query) {
                return $this->update($query);
            });
        }

        if (Str::startsWith($table, 'entry_')) {
            $table = Str::after($table, 'entry_');
            $type = 'entry';
        }

        foreach ($data as $key => $value) {
            $payload[$this->queryGrammar->removeQualifiedColumn($table, $key)] = $value;
        }

        switch ($type) {
            case 'entry':
                return $this->updateEntry($id, $payload);
            case 'customer':
                return $this->updateCustomer($id, $payload);
            default:
                break;
        }

        throw new RuntimeException('This database engine does not support updates for [' . $index . '].');
    }

    /**
     * Undocumented function
     *
     * @param string|int $structure
     * @param array $data
     * @return bool
     */
    protected function updateEntry($id, $data)
    {
        if (!isset($data['revision_publish'])) {
            $data['revision_publish'] = true;
        }

        $pdo = $this->getPdo();
        $client = $pdo->getApiClient();
        $client->put('builder/structures/entry/' . $id, $data);
        return true;
    }

    protected function updateCustomer($id, $data)
    {
        $pdo = $this->getPdo();
        $client = $pdo->getApiClient();
        $client->put('relations/customers/customer/' . $id, $data);
        return true;
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
        $type = $index;

        if (!$id && $clause) {
            return $this->bulk($index, [], $clause, function ($query) {
                return $this->delete($query);
            });
        }

        if (Str::startsWith($table, 'entry_')) {
            $type = 'entry';
            $table = Str::after($table, 'entry_');
        }

        switch ($type) {
            case 'entry':
                return $this->deleteEntry($id);
            case 'customer':
                return $this->deleteCustomer($id);
            default:
                break;
        }

        throw new RuntimeException('This database engine does not support deletes of [' . $index . '].');
    }

    protected function deleteEntry($id)
    {
        $pdo = $this->getPdo();
        $client = $pdo->getApiClient();
        $client->delete('builder/structures/entry/' . $id);
        return true;
    }

    protected function deleteCustomer($id)
    {
        $pdo = $this->getPdo();
        $client = $pdo->getApiClient();
        $client->delete('relations/customers/customer/' . $id);
        return true;
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

    /**
     * Get a Doctrine Schema Column instance.
     *
     * @param  string  $table
     * @param  string  $column
     * @return \Doctrine\DBAL\Schema\Column
     */
    public function getDoctrineColumn($table, $column)
    {
        $table = Structure::normalizeIndexName($table);
        $fields = [
            ...array_map(fn ($field) => (object) [
                'alias' => $field,
                'type' => 'string'
            ], Field::RESERVED_FIELDS),
            ...$this->getPdo()
                ->getAPIClient()
                ->get('builder/structures/' . $table . '/fields')
        ];

        foreach ($fields as $field) {
            if ($field->alias === $column) {
                return new Field($field);
            }
        }
    }
}
