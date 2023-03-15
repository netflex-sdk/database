<?php

namespace Netflex\Database\Driver\Schema\Grammars;

use Illuminate\Database\Schema\Grammars\Grammar;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Fluent;

use Netflex\Database\DBAL\Command;
use Netflex\Database\DBAL\Column;
use Netflex\Database\Driver\Connection;

class CreateColumn
{
    /**
     * Compile a create field command.
     * @return array
     */
    public static function compile(Grammar $grammar, Blueprint $blueprint, Fluent $command, Connection $connection)
    {
        return array_values(
            array_filter(
                array_map(fn ($column) => static::compileCreateField(
                    $blueprint,
                    $column,
                    $connection
                ), $blueprint->getAddedColumns())
            )
        );
    }

    protected static function compileCreateField(Blueprint $blueprint, $column, Connection $connection)
    {
        if (Column::isReserved($connection, $column->name)) {
            return null;
        }

        $field = [
            'name' => Column::normalizeName($column->name),
            'table' => $blueprint->getTable(),
            'type' => $column->type,
            'column' => $column->name,
        ];

        $config = [];

        if (isset($column->default)) {
            $config['default_value'] = [
                'type' => 'textField',
                'value' => $column->default
            ];
        }

        if (!empty($config)) {
            $field['config'] = json_encode($config);
        }

        return [
            'command' => Command::TABLE_COLUMN_ADD,
            'arguments' => $field
        ];
    }
}
