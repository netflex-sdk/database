<?php

namespace Netflex\Database\Driver\Schema\Grammars;

use Illuminate\Database\Schema\Grammars\Grammar;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Fluent;
use Illuminate\Database\Connection;
use Illuminate\Support\Str;

use Netflex\Database\Driver\Schema\Field;
use Netflex\Database\Driver\Command;

class ListFields
{
    /**
     * Compile a alter field command.
     * @return array
     */
    public static function compile(Grammar $grammar, string $table)
    {
        if (Str::startsWith($table, 'entry_')) {
            $table = Str::after($table, 'entry_');;
        }

        return [
            'command' => Command::LIST_FIELDS,
            'arguments' => [
                'structure' => $table,
            ]
        ];
    }
}
