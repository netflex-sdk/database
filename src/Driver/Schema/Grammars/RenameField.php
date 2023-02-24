<?php

namespace Netflex\Database\Driver\Schema\Grammars;

use Illuminate\Database\Schema\Grammars\Grammar;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Fluent;
use Illuminate\Database\Connection;

use Netflex\Database\Driver\Schema\Field;
use Netflex\Database\Driver\Command;

class RenameField
{
    /**
     * Compile a alter field command.
     * @return array
     */
    public static function compile(Grammar $grammar, Blueprint $blueprint, Fluent $command, Connection $connection)
    {
        if (in_array($command->to, Field::RESERVED_FIELDS)) {
            return [];
        }

        if (in_array($command->from, Field::RESERVED_FIELDS)) {
            return [];
        }

        return [
            [
                'command' => Command::RENAME_STRUCTURE_FIELD,
                'arguments' => [
                    'structure' => $blueprint->getTable(),
                    'name' => Field::normalizeName($command->to),
                    'from' => $command->from,
                    'to' => $command->to
                ]
            ]
        ];
    }
}
