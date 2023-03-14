<?php

namespace Netflex\Database\Driver\Schema\Grammars;

use Illuminate\Database\Schema\Grammars\Grammar;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Fluent;
use Illuminate\Database\Connection;

use Netflex\Database\Driver\Command;
use Netflex\Database\Driver\Schema\Field;

class DeleteFieldIfExists
{
    protected static $reservedFields = [
        'id',
        'name',
        'directory_id',
        'revision',
        'published',
        'userid',
        'use_time',
        'start',
        'stop',
        'public',
    ];

    /**
     * Compile a create field command.
     * @return array
     */
    public static function compile(Grammar $grammar, Blueprint $blueprint, Fluent $command, Connection $connection)
    {
        return array_values(
            array_filter(
                array_map(fn ($column) => static::compileDeleteField(
                    $blueprint,
                    $column,
                    $connection
                ), $command->columns)
            )
        );
    }

    protected static function compileDeleteField(Blueprint $blueprint, $column)
    {
        if (Field::isReserved($column)) {
            return null;
        }

        return [
            'command' => Command::TABLE_COLUMN_DROP_IF_EXISTS,
            'arguments' => [
                'table' => $blueprint->getTable(),
                'column' => $column,
            ]
        ];
    }
}
