<?php

namespace Netflex\Database\Driver\Doctrine;

use Doctrine\DBAL\Schema\AbstractSchemaManager;

use Netflex\Database\Driver\Connection;
use Doctrine\DBAL\Connection as DoctrineConnection;
use Netflex\Database\Driver\Schema\Field;
use Netflex\Database\Driver\Schema\Grammars\ListFields;
use Doctrine\DBAL\Schema\Column;

class SchemaManager extends AbstractSchemaManager
{
    protected $connection;

    public function __construct(Connection $connection, DoctrineConnection $doctrineConnection)
    {
        parent::__construct($doctrineConnection);
        $this->connection = $connection;
    }

    /**
     * @param Field $tableColumn
     * @return Column
     */
    protected function _getPortableTableColumnDefinition($tableColumn)
    {
        return $tableColumn->toDoctrineColumn($tableColumn);
    }

    public function listTableColumns($table, $database = null)
    {
        $fields = Field::getFields($this->connection->getPdo()->getAPIClient(), $table);;
        return array_map(fn ($field) => $this->_getPortableTableColumnDefinition($field), $fields);
    }

    public function listTableForeignKeys($table, $database = null)
    {
        return [];
    }

    public function listTableIndexes($table, $currentDatabase = null)
    {
        return [];
    }
}
