<?php

namespace Netflex\Database\Driver\Schema\Grammars;

use Illuminate\Database\Schema\Grammars\Grammar;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Fluent;
use Illuminate\Database\Connection;

use Netflex\Database\Driver\Command;
use Netflex\Database\Driver\Schema\Field;

class CreateField
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
                array_map(fn ($column) => static::compileCreateField(
                    $blueprint,
                    $column,
                    $connection
                ), $blueprint->getAddedColumns())
            )
        );
    }

    protected static function compileCreateField(Blueprint $blueprint, $column)
    {
        if (in_array($column->name, Field::RESERVED_FIELDS)) {
            return null;
        }

        $field = [
            'name' => Field::normalizeName($column->name),
            'structure' => $blueprint->getTable(),
            'type' => $column->type,
            'alias' => $column->name,
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
            'command' => Command::CREATE_STRUCTURE_FIELD,
            'arguments' => $field
        ];
    }
}
