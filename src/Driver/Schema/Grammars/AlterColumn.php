<?php

namespace Netflex\Database\Driver\Schema\Grammars;

use Illuminate\Database\Schema\Grammars\Grammar;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Fluent;
use Illuminate\Database\Connection;

use Netflex\Database\DBAL\Column;
use Netflex\Database\DBAL\Command;

class AlterColumn
{
    /**
     * Compile a alter field command.
     * @return array
     */
    public static function compile(Grammar $grammar, Blueprint $blueprint, Fluent $command, Connection $connection)
    {
        if (Column::isReserved($command->to)) {
            return [];
        }

        if (Column::isReserved($command->from)) {
            return [];
        }

        return [
            [
                'command' => Command::TABLE_COLUMN_ALTER,
                'arguments' => [
                    'table' => $blueprint->getTable(),
                    'name' => Column::normalizeName($command->to),
                    'from' => $command->from,
                    'to' => $command->to
                ]
            ]
        ];
    }
}
