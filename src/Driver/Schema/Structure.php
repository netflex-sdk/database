<?php

namespace Netflex\Database\Driver\Schema;

use Illuminate\Support\Str;

final class Structure
{
    public static function normalizeIndexName($name)
    {
        if (Str::startsWith($name, 'entry_')) {
            return Str::after($name, 'entry_');
        }

        return $name;
    }

    public static function normalizeName($name)
    {
        return Str::replace('_', ' ', Str::title($name));
    }
}
