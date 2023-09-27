<?php

namespace Netflex\Database\Driver\Schema;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Schema\Grammars\Grammar;

use Netflex\Database\DBAL\Command;
use Netflex\Database\DBAL\Grammars\SelectColumns;

use Netflex\Database\DBAL\Contracts\Connection;

use Netflex\Database\Driver\Schema\Grammars\AlterColumn;
use Netflex\Database\Driver\Schema\Grammars\CreateColumn;
use Netflex\Database\Driver\Schema\Grammars\CreateTable;
use Netflex\Database\Driver\Schema\Grammars\DropColumnIfExists;
use Netflex\Database\Driver\Schema\Grammars\DropTable;
use Netflex\Database\Driver\Schema\Grammars\DropTableIfExists;

class SchemaGrammar extends Grammar
{
    /** @var Connection */
    protected $connection;

    public function __construct(Connection $connection)
    {
        $this->connection = $connection;
    }

    public function compileTableExists()
    {
        return Command::TABLE_EXISTS;
    }

    public function compileColumnListing($table)
    {
        return SelectColumns::compile($this->connection, $table);
    }

    public function compileCreate(Blueprint $blueprint, $command, $connection)
    {
        return CreateTable::compile($this, $blueprint, $command, $connection);
    }

    /**
     * Indicate that the table should be dropped.
     */
    public function compileDrop(Blueprint $blueprint, $command, $connection)
    {
        return DropTable::compile($this, $blueprint, $command, $connection);
    }

    /**
     * Indicate that the table should be dropped if it exists.
     */
    public function compileDropIfExists(Blueprint $blueprint, $command, $connection)
    {
        return DropTableIfExists::compile($this, $blueprint, $command, $connection);
    }

    public function compileAdd(Blueprint $blueprint, $command, $connection)
    {
        return CreateColumn::compile($this, $blueprint, $command, $connection);
    }

    /**
     * Indicate that the given columns should be dropped.
     */
    public function compileDropColumn(Blueprint $blueprint, $command, $connection)
    {
        return DropColumnIfExists::compile($this, $blueprint, $command, $connection);
    }

    /**
     * Indicate that the given column should be renamed.
     */
    public function compileRenameColumn(Blueprint $blueprint, $command, $connection)
    {
        return AlterColumn::compile($this, $blueprint, $command, $connection);
    }
}
