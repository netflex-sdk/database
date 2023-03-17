<?php

namespace Netflex\Database\Driver\Schema\Grammars;

use Illuminate\Support\Str;

use Netflex\Database\DBAL\Command;
use Netflex\Database\Driver\Connection;

class TableExists
{
    /**
     * Compile a alter field command.
     * @return array
     */
    public static function compile(Connection $connection, string $table)
    {
        $prefix = $connection->getTablePrefix();

        if (Str::startsWith($table, $prefix)) {
            $table = Str::after($table, $prefix);;
        }

        return [
            'command' => Command::TABLE_EXISTS,
            'arguments' => [
                'table' => $table
            ]
        ];
    }
}
