<?php

namespace Netflex\Database\Driver\Doctrine;

use Netflex\Database\DBAL\Column;
use Netflex\Database\Driver\Connection;

use Doctrine\DBAL\Schema\AbstractSchemaManager;
use Doctrine\DBAL\Connection as DoctrineConnection;
use Doctrine\DBAL\Schema\Column as DoctrineColumn;

class SchemaManager extends AbstractSchemaManager
{
    protected $connection;

    public function __construct(Connection $connection, DoctrineConnection $doctrineConnection)
    {
        parent::__construct($doctrineConnection);
        $this->connection = $connection;
    }

    /**
     * @param Column $tableColumn
     * @return DoctrineColumn
     */
    protected function _getPortableTableColumnDefinition($tableColumn)
    {
        return $tableColumn->toDoctrineColumn($tableColumn);
    }

    public function listTableColumns($table, $database = null)
    {
        $fields = Column::getFields($this->connection->getPdo()->getAPIClient(), $table);;
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
