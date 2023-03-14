<?php

namespace Netflex\Database\Driver\Schema;

use Illuminate\Support\Str;

use Netflex\API\Client;

use Doctrine\DBAL\Exception;

use Doctrine\DBAL\Schema\Column;
use Doctrine\DBAL\Types\ArrayType;
use Doctrine\DBAL\Types\BooleanType;
use Doctrine\DBAL\Types\IntegerType;
use Doctrine\DBAL\Types\SimpleArrayType;
use Doctrine\DBAL\Types\TextType;
use Doctrine\DBAL\Types\Type;

final class Field
{
    protected array $field;

    protected function __construct(array $field)
    {
        $this->field = $field;
    }

    protected const RESERVED_FIELDS = [
        'id' => [
            'type' => 'integer',
            'notnull' => true,
            'autoincrement' => true,
            'comment' => 'Primary key'
        ],
        'name' => [
            'type' => 'text',
            'notnull' => true,
            'comment' => 'Name of the entry'
        ],
        'directory_id' => [
            'type' => 'integer',
            'notnull' => true,
            'comment' => 'The directory this entry belongs to'
        ],
        'revision' => [
            'type' => 'integer',
            'autoincrement' => true,
            'notnull' => true,
            'comment' => 'Current revision'
        ],
        'published' => [
            'type' => 'boolean',
            'notnull' => true,
            'comment' => 'Whether or not this entry is published'
        ],
        'userid' => [
            'type' => 'integer',
        ],
        'use_time' => [
            'type' => 'boolean',
            'notnull' => true,
            'comment' => 'Whether or not this entry uses time based publishing'
        ],
        'start' => [
            'type' => 'datetime',
            'notnull' => false,
            'comment' => 'From when this entry should be published'
        ],
        'stop' => [
            'type' => 'datetime',
            'notnull' => false,
            'comment' => 'When this entry should be unpublished'
        ],
        'public' => [
            'type' => 'boolean',
            'notnull' => false,
            'comment' => 'Not used'
        ]
    ];

    public static function reserved(): array
    {
        return array_keys(static::RESERVED_FIELDS);
    }

    public static function isReserved(string $name): bool
    {
        return in_array($name, static::reserved());
    }

    public static function normalizeName($name)
    {
        return Str::replace('_', ' ', Str::title($name));
    }

    public static function getFields(Client $connection, string $table): array
    {
        return collect(
            [
                ...collect(static::RESERVED_FIELDS)
                    ->map(fn ($field, $key) => [
                        'column'  => $key,
                        'type'   => $field['type'],
                        'notnull' => $field['notnull'] ?? false,
                        'default' => $field['default'] ?? null,
                        'autoincrement' => $field['autoincrement'] ?? false,
                        'comment' => $field['comment'] ?? '',
                    ])->values(),
                ...array_map(
                    fn ($field) => [
                        'column'  => $field['alias'],
                        'type'   => $field['type'],
                        'notnull' => false,
                        'default' => data_get($field, 'config.default_value.value', null),
                        'comment' => $field['description'] ?? '',
                    ],
                    $connection->get('builder/structures/' . $table . '/fields', true)
                )
            ]
        )
            ->map(fn ($field) => new static($field))
            ->all();
    }

    public function toArray(): array
    {
        return $this->field;
    }

    public function toDoctrineColumn(): Column
    {
        return new Column(
            $this->name(),
            $this->type(),
            $this->options()
        );
    }

    public function name(): string
    {
        return $this->field['column'];
    }

    public function type(): Type
    {
        switch ($this->field['type']) {
            case 'entry':
                return new IntegerType;
            case 'checkbox':
                return new BooleanType;
            case 'collection':
            case 'textarea':
            case 'editor-large':
            case 'editor-small':
                return new TextType;
            case 'image':
            case 'file':
                return new ArrayType;
            case 'entries':
                return new SimpleArrayType;
            default:
                try {
                    return Type::getType($this->field['type']);
                } catch (Exception $e) {
                    dump($this->field['type']);
                    return new TextType;
                }
        }
    }

    public function options(): array
    {
        return collect($this->field)
            ->except(['field', 'type'])
            ->toArray();
    }
}
