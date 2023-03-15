<?php

namespace Netflex\Database\Driver\Schema\Grammars;

use Illuminate\Database\Schema\Grammars\Grammar;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Fluent;
use Illuminate\Database\Connection;

use Netflex\Database\DBAL\Command;
use Netflex\Database\DBAL\Table;
use Netflex\Database\Driver\Schema\Grammars\CreateColumn;

class CreateTable
{
    /**
     * Compile a create field command.
     * @return array
     */
    public static function compile(Grammar $grammar, Blueprint $blueprint, Fluent $command, Connection $connection)
    {
        return [
            [
                'command' => Command::TABLE_CREATE,
                'arguments' => [
                    'name' => Table::normalizeName($blueprint->getTable()),
                    'table' => $blueprint->getTable()
                ],
            ],
            ...CreateColumn::compile($grammar, $blueprint, $command, $connection)
        ];
    }
}
