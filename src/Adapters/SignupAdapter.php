<?php

namespace Netflex\Database\Adapters;

use Netflex\Database\DBAL\Adapters\AbstractAdapter;

class SignupAdapter extends AbstractAdapter
{
    protected array $reservedTables = ['signup'];

    protected array $reservedFields = [
        'id' => [
            'type' => 'integer',
            'notnull' => true,
            'autoincrement' => true,
            'comment' => 'Primary key'
        ]
    ];

    protected function getTableName(string $table): string
    {
        return 'signup';
    }
}
