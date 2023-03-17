<?php

namespace Netflex\Database\Adapters;

use Netflex\Database\DBAL\Adapters\AbstractAdapter;

class OrderAdapter extends AbstractAdapter
{
    protected array $reservedTables = ['order'];

    protected array $reservedFields = [
        'id' => [
            'type' => 'integer',
            'notnull' => true,
            'autoincrement' => true,
            'comment' => 'Primary key'
        ],
        'secret' => [
            'type' => 'string',
            'notnull' => true,
            'comment' => 'Secret key'
        ],
    ];

    protected function getTableName(string $table): string
    {
        return 'order';
    }
}
