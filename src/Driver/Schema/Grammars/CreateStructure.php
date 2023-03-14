<?php

namespace Netflex\Database\Driver\Schema\Grammars;

use Illuminate\Database\Schema\Grammars\Grammar;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Fluent;
use Illuminate\Database\Connection;

use Netflex\Database\Driver\Command;
use Netflex\Database\Driver\Schema\Structure;
use Netflex\Database\Driver\Schema\Grammars\CreateField;

class CreateStructure
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
                    'name' => Structure::normalizeName($blueprint->getTable()),
                    'table' => $blueprint->getTable()
                ],
            ],
            ...CreateField::compile($grammar, $blueprint, $command, $connection)
        ];
    }
}
