<?php

namespace Netflex\Database\Adapters;

use Closure;

use Netflex\Database\DBAL\Adapters\AbstractAdapter;
use Netflex\Database\DBAL\Concerns\PerformsQueries;
use Netflex\Database\DBAL\PDOStatement;

final class PageAdapter extends AbstractAdapter
{
    use PerformsQueries {
        select as performSelect;
    }

    protected array $reservedTableNames = [
        'page'
    ];

    protected array $reservedFields = [
        'id' => [
            'type' => 'integer',
            'notnull' => true,
            'autoincrement' => true,
            'comment' => 'Primary key'
        ],
        'group_id' => [
            'type' => 'integer',
            'notnull' => false,
            'comment' => 'Group ID'
        ],
        'type' => [
            'type' => 'string',
            'notnull' => false,
            'comment' => 'Type'
        ],
        'name' => [
            'type' => 'string',
            'notnull' => false,
            'comment' => 'Name'
        ],
        'url' => [
            'type' => 'string',
            'notnull' => false,
            'comment' => 'URL'
        ],
        'children_inherit_url' => [
            'type' => 'boolean',
            'notnull' => false,
            'comment' => 'Children inherit URL'
        ],
        'template' => [
            'type' => 'string',
            'notnull' => false,
            'comment' => 'Template'
        ],
        'published' => [
            'type' => 'boolean',
            'notnull' => false,
            'comment' => 'Published'
        ],
        'revision' => [
            'type' => 'integer',
            'notnull' => false,
            'comment' => 'Revision'
        ],
        'created' => [
            'type' => 'datetime',
            'notnull' => false,
            'comment' => 'Created'
        ],
        'updated' => [
            'type' => 'datetime',
            'notnull' => false,
            'comment' => 'Updated'
        ],
        'use_time' => [
            'type' => 'boolean',
            'notnull' => false,
            'comment' => 'Use time'
        ],
        'start' => [
            'type' => 'datetime',
            'notnull' => false,
            'comment' => 'Start'
        ],
        'stop' => [
            'type' => 'datetime',
            'notnull' => false,
            'comment' => 'Stop'
        ],
        'visible' => [
            'type' => 'boolean',
            'notnull' => false,
            'comment' => 'Visible'
        ],
        'visible_nav' => [
            'type' => 'boolean',
            'notnull' => false,
            'comment' => 'Visible in navigation'
        ],
        'visible_subnav' => [
            'type' => 'boolean',
            'notnull' => false,
            'comment' => 'Visible in subnavigation'
        ],
        'nav_hidden_xs' => [
            'type' => 'boolean',
            'notnull' => false,
            'comment' => 'Hidden in navigation on extra small screens'
        ],
        'nav_hidden_sm' => [
            'type' => 'boolean',
            'notnull' => false,
            'comment' => 'Hidden in navigation on small screens'
        ],
        'nav_hidden_md' => [
            'type' => 'boolean',
            'notnull' => false,
            'comment' => 'Hidden in navigation on medium screens'
        ],
        'nav_hidden_lg' => [
            'type' => 'boolean',
            'notnull' => false,
            'comment' => 'Hidden in navigation on large screens'
        ],
        'nav_target' => [
            'type' => 'string',
            'notnull' => false,
            'comment' => 'Navigation target'
        ],
        'parent_id' => [
            'type' => 'integer',
            'notnull' => false,
            'comment' => 'Parent ID'
        ],
        'image' => [
            'type' => 'string',
            'notnull' => false,
            'comment' => 'Image'
        ],
        'icon' => [
            'type' => 'string',
            'notnull' => false,
            'comment' => 'Icon'
        ],
        'title' => [
            'type' => 'string',
            'notnull' => false,
            'comment' => 'Title'
        ],
        'description' => [
            'type' => 'string',
            'notnull' => false,
            'comment' => 'Description'
        ],
        'keywords' => [
            'type' => 'array',
            'notnull' => false,
            'comment' => 'Keywords'
        ],
        'navtitle' => [
            'type' => 'string',
            'notnull' => false,
            'comment' => 'Navigation title'
        ],
        'sorting' => [
            'type' => 'integer',
            'notnull' => false,
            'comment' => 'Sorting'
        ],
        'lang' => [
            'type' => 'string',
            'notnull' => false,
            'comment' => 'Language'
        ],
        'add_to_head' => [
            'type' => 'string',
            'notnull' => false,
            'comment' => 'Add to head'
        ],
        'add_to_bodyclose' => [
            'type' => 'string',
            'notnull' => false,
            'comment' => 'Add to body close'
        ],
        'body_class' => [
            'type' => 'string',
            'notnull' => false,
            'comment' => 'Body class'
        ],
        'public' => [
            'type' => 'boolean',
            'notnull' => false,
            'comment' => 'Public'
        ],
        'authgroups' => [
            'type' => 'array',
            'notnull' => false,
            'comment' => 'Authgroups'
        ],
        'author' => [
            'type' => 'string',
            'notnull' => false,
            'comment' => 'Author'
        ],
        'userid' => [
            'type' => 'integer',
            'notnull' => false,
            'comment' => 'User ID'
        ],
        'config' => [
            'type' => 'json',
            'notnull' => false,
            'comment' => 'Config'
        ],
        'children_inherit_permissions' => [
            'type' => 'boolean',
            'notnull' => false,
            'comment' => 'Children inherit permissions'
        ],
    ];

    public function select(PDOStatement $statement, array $arguments, Closure $callback): bool
    {
        $arguments['table'] = 'page';

        return $this->performSelect($statement, $arguments, $callback);
    }
}
