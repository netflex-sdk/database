<?php

namespace Netflex\Database\Driver\Schema;

use Illuminate\Database\Schema\Builder;

use Netflex\Database\DBAL\Command;
use Netflex\Database\Driver\Connection;
use Netflex\Database\Driver\Schema\Grammars\TableExists;
use Netflex\Database\Driver\Schema\SchemaGrammar;

class SchemaBuilder extends Builder
{
    /** @var Connection */
    protected $connection;

    public function __construct(Connection $connection)
    {
        if (!$connection->getSchemaGrammar()) {
            $connection->useDefaultSchemaGrammar();
        }

        parent::__construct($connection);
        $this->grammar = new SchemaGrammar($connection);
    }

    public function hasTable($table)
    {
        $pdo = $this->connection->getPdo();

        $statement = $pdo->prepare(TableExists::compile($this->connection, $table));

        return $statement->execute();
    }
}
