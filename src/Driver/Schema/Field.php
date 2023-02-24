<?php

namespace Netflex\Database\Driver\Schema;

use Illuminate\Support\Str;

final class Field
{
    protected $field;

    public function __construct($field)
    {
        $this->field = $field;
    }

    const RESERVED_FIELDS = [
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

    public static function normalizeName($name)
    {
        return Str::replace('_', ' ', Str::title($name));
    }

    public function getType()
    {
        return new class($this->field)
        {
            protected $field;

            public function __construct($field)
            {
                $this->field = $field;
            }

            public function getName()
            {
                return $this->field->type;
            }
        };
    }
}
