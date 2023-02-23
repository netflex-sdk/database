<?php

namespace Netflex\Database\Driver;

use PDO as BasePDO;
use RuntimeException;

use Netflex\API\Contracts\APIClient;
use Netflex\API\Facades\API;
use Netflex\Query\Builder;

final class PDO extends BasePDO
{
    protected string $connection;

    protected $lastInsertId = null;

    public function __construct(array $parameters)
    {
        parent::__construct('sqlite::memory:');
        $this->connection = $parameters['connection'] ?? 'default';
    }

    public function getAPIClient(): APIClient
    {
        return API::connection($this->connection);
    }

    public function setLastInsertId($id)
    {
        $this->lastInsertId = $id;
    }

    /**
     * Returns the ID of the last inserted row or sequence value
     * @param string|null $seqname
     * @return string|false
     **/
    public function lastInsertId($seqname = null)
    {
        if ($this->lastInsertId) {
            return $this->lastInsertId;
        }

        return false;
    }

    /**
     * Initiates a transaction
     * @return bool
     **/
    public function beginTransaction(): bool
    {
        throw new RuntimeException('This database engine does not support transactions.');
    }

    /**
     * Commits a transaction
     * @return bool
     **/
    public function commit(): bool
    {
        throw new RuntimeException('This database engine does not support transactions.');
    }

    /**
     * Fetch the SQLSTATE associated with the last operation on the database handle
     * @return string|null
     **/
    public function errorCode(): ?string
    {
        return null;
    }

    /**
     * Fetch extended error information associated with the last operation on the database handle
     * @return array
     **/
    public function errorInfo(): array
    {
        return [];
    }

    /**
     * Execute an SQL statement and return the number of affected rows
     * @return int|false
     **/
    public function exec($query, ?array $output = null, ?int $return_var = null)
    {
        throw new RuntimeException('This database engine does not support transactions.');
        $statement = new PDOStatement($this, $query);
        $statement->execute();
        return $statement->affectedRows;
    }

    /**
     * Retrieve a database connection attribute
     * @param int $attribute
     * @return mixed
     **/
    public function getAttribute($attribute)
    {
        return null;
    }

    /**
     * Return an array of available PDO drivers
     * @return array
     **/
    public static function getAvailableDrivers(): array
    {
        $drivers = array_merge(
            parent::getAvailableDrivers(),
            ['netflex']
        );

        sort($drivers);

        return array_values(array_unique($drivers));
    }

    /**
     * Checks if inside a transaction
     * @return bool
     **/
    public function inTransaction(): bool
    {
        return false;
    }

    /**
     * Prepares a statement for execution and returns a statement object
     * @param string $statement This must be a valid SQL statement for the target database server.
     * @param array|null $options
     * @return PDOStatement|false
     **/
    public function prepare($statement, $options = null)
    {
        return new PDOStatement($this, $statement);
    }

    /**
     * Prepares and executes an SQL statement without placeholders
     * @return PDOStatement|false
     **/
    public function query(string $query, ?int $fetchMode = null, ...$fetchModeArgs)
    {
        return new PDOStatement($this, $query);
    }

    /**
     * Quotes a string for use in a query
     * @param string $string The string to be quoted.
     * @param int $paramtype Provides a data type hint for drivers that have alternate quoting styles.
     * @return string|false
     **/
    public function quote($string, $paramtype = BasePDO::PARAM_STR)
    {
        $builder = new Builder();
        return $builder->escapeValue($string);
    }

    /**
     * Rolls back a transaction
     * @return bool
     **/
    public function rollBack(): bool
    {
        throw new RuntimeException('This database engine does not support transactions.');
    }

    /**
     * Set an attribute
     * @return bool
     **/
    public function setAttribute($attribute, $value): bool
    {
        return false;
    }
}
