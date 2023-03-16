<?php

namespace Netflex\Database\Driver\Schema\Grammars;

use Illuminate\Database\Schema\Grammars\Grammar;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Fluent;

use Netflex\Database\DBAL\Command;
use Netflex\Database\DBAL\Table;
use Netflex\Database\DBAL\Contracts\Connection;
use Netflex\Database\Driver\Schema\Grammars\CreateColumn;

class CreateTable
{
    /**
     * Compile a create field command.
     * @return array
     */
    public static function compile(Grammar $grammar, Blueprint $blueprint, Fluent $command, Connection $connection)
    {
        $payload = [
            'name' => Table::normalizeName($blueprint->getTable()),
            'table' => $blueprint->getTable()
        ];

        $config = [];

        $reservedTables = $connection->getAdapter()->getReservedTableNames();

        if (in_array($blueprint->getTable(), $reservedTables)) {
            $config['disableRevisions'] = ['type' => 'boolean', 'value' => true];
            $config['hide_structure_from_listing'] = ['type' => 'boolean', 'value' => true];
        }

        if (!empty($config)) {
            $payload['config'] = json_encode($config);
        }

        return [
            [
                'command' => Command::TABLE_CREATE,
                'arguments' => $payload,
            ],
            ...CreateColumn::compile($grammar, $blueprint, $command, $connection)
        ];
    }
}
