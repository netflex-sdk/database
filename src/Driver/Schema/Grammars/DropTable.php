<?php

namespace Netflex\Database\Driver\Schema\Grammars;

use Illuminate\Database\Schema\Grammars\Grammar;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Fluent;
use Illuminate\Database\Connection;

use Netflex\Database\DBAL\Command;

class DropTable
{
    /**
     * Compile a delete structure command.
     * @return array
     */
    public static function compile(Grammar $grammar, Blueprint $blueprint, Fluent $command, Connection $connection)
    {
        return [
            [
                'command' => Command::TABLE_DROP,
                'arguments' => [
                    'column' => $blueprint->getTable()
                ]
            ]
        ];
    }
}
