<?php

namespace Netflex\Database\Driver\Schema;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Schema\Grammars\Grammar;

use Netflex\Database\Driver\Command;
use Netflex\Database\Driver\Schema\Grammars\CreateField;
use Netflex\Database\Driver\Schema\Grammars\CreateStructure;
use Netflex\Database\Driver\Schema\Grammars\DeleteField;
use Netflex\Database\Driver\Schema\Grammars\DeleteFieldIfExists;
use Netflex\Database\Driver\Schema\Grammars\DeleteStructure;
use Netflex\Database\Driver\Schema\Grammars\DeleteStructureIfExists;
use Netflex\Database\Driver\Schema\Grammars\ListFields;
use Netflex\Database\Driver\Schema\Grammars\RenameField;

class SchemaGrammar extends Grammar
{
    public function compileTableExists()
    {
        return Command::STRUCTURE_EXISTS;
    }

    public function compileColumnListing($table)
    {
        return ListFields::compile($this, $table);
    }

    public function compileCreate(Blueprint $blueprint, $command, $connection)
    {
        return CreateStructure::compile($this, $blueprint, $command, $connection);
    }

    /**
     * Indicate that the table should be dropped.
     */
    public function compileDrop(Blueprint $blueprint, $command, $connection)
    {
        return DeleteStructure::compile($this, $blueprint, $command, $connection);
    }

    /**
     * Indicate that the table should be dropped if it exists.
     */
    public function compileDropIfExists(Blueprint $blueprint, $command, $connection)
    {
        return DeleteStructureIfExists::compile($this, $blueprint, $command, $connection);
    }

    public function compileAdd(Blueprint $blueprint, $command, $connection)
    {
        return CreateField::compile($this, $blueprint, $command, $connection);
    }

    /**
     * Indicate that the given columns should be dropped.
     */
    public function compileDropColumn(Blueprint $blueprint, $command, $connection)
    {
        return DeleteFieldIfExists::compile($this, $blueprint, $command, $connection);
    }

    /**
     * Indicate that the given column should be renamed.
     */
    public function compileRenameColumn(Blueprint $blueprint, $command, $connection)
    {
        return RenameField::compile($this, $blueprint, $command, $connection);
    }
}
