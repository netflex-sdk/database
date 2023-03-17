<?php

namespace Netflex\Database\Adapters;

use Netflex\Database\DBAL\Adapters\AbstractAdapter;

class FileAdapter extends AbstractAdapter
{
    protected array $reservedTables = ['file'];

    protected array $reservedFields = [
        'id' => [
            'type' => 'integer',
            'notnull' => true,
            'autoincrement' => true,
            'comment' => 'Primary key'
        ],
        'folder_id' => [
            'type' => 'integer',
            'notnull' => true,
            'comment' => 'Folder ID'
        ],
        'name' => [
            'type' => 'string',
            'notnull' => true,
            'comment' => 'File name'
        ],
        'path' => [
            'type' => 'string',
            'notnull' => true,
            'comment' => 'File path'
        ],
        'description' => [
            'type' => 'string',
            'notnull' => true,
            'comment' => 'File description'
        ],
        'tags' => [
            'type' => 'array',
            'notnull' => true,
            'comment' => 'File tags'
        ],
        'size' => [
            'type' => 'integer',
            'notnull' => true,
            'comment' => 'File size'
        ],
        'type' => [
            'type' => 'string',
            'notnull' => true,
            'comment' => 'File type'
        ],
        'created' => [
            'type' => 'datetime',
            'notnull' => true,
            'comment' => 'File creation date'
        ],
        'userid' => [
            'type' => 'integer',
            'notnull' => true,
            'comment' => 'User ID'
        ],
        'public' => [
            'type' => 'boolean',
            'notnull' => true,
            'comment' => 'Public'
        ],
        'related_entries' => [
            'type' => 'array',
            'notnull' => true,
            'comment' => 'Related entries'
        ],
        'related_customers' => [
            'type' => 'array',
            'notnull' => true,
            'comment' => 'Related customers'
        ],
        'img_width' => [
            'type' => 'integer',
            'notnull' => true,
            'comment' => 'Image width'
        ],
        'img_height' => [
            'type' => 'integer',
            'notnull' => true,
            'comment' => 'Image height'
        ],
        'img_res' => [
            'type' => 'string',
            'notnull' => true,
            'comment' => 'Image resolution'
        ],
        'img_lat' => [
            'type' => 'string',
            'notnull' => true,
            'comment' => 'Image latitude'
        ],
        'img_lon' => [
            'type' => 'string',
            'notnull' => true,
            'comment' => 'Image longitude'
        ],
        'img_artist' => [
            'type' => 'string',
            'notnull' => true,
            'comment' => 'Image artist'
        ],
        'img_desc' => [
            'type' => 'string',
            'notnull' => true,
            'comment' => 'Image description'
        ],
        'img_alt' => [
            'type' => 'string',
            'notnull' => true,
            'comment' => 'Image alt text'
        ],
        'img_o_date' => [
            'type' => 'datetime',
            'notnull' => true,
            'comment' => 'Image original date'
        ],
        'foldercode' => [
            'type' => 'string',
            'notnull' => true,
            'comment' => 'Folder code'
        ],
    ];

    protected function getTableName(string $table): string
    {
        return 'file';
    }
}
