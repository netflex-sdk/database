<?php

namespace Netflex\Database\Adapters;

use Closure;
use Exception;
use RuntimeException;
use PDOException;

use GuzzleHttp\Exception\ClientException;

use Illuminate\Support\Str;

use Netflex\Database\DBAL\Adapters\AbstractAdapter;
use Netflex\Database\DBAL\PDOStatement;
use Netflex\Database\DBAL\Command;
use Netflex\Database\DBAL\Column;
use Netflex\Database\DBAL\Concerns\PerformsQueries;
use Netflex\Database\DBAL\Contracts\Connection;

final class EntryAdapter extends AbstractAdapter
{
    use PerformsQueries;

    protected string $tablePrefix = 'entry_';

    protected array $reservedTableNames = [
        'failed_jobs',
        'migrations',
        'password_reset_tokens',
        'password_resets',
        'personal_access_tokens',
        'users'
    ];

    protected array $reservedFields = [
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

    public function insert(PDOStatement $statement, array $arguments, Closure $callback): bool
    {
        $table = $arguments['table'];
        $data = $arguments['payload'] ?? [];

        if (!isset($data['revision_publish'])) {
            $data['revision_publish'] = true;
        }

        if (!isset($data['name'])) {
            $data['name'] = Str::uuid();
        }

        $pdo = $statement->getPDO();

        try {
            $pdo->setLastInsertId(null);

            $response = $pdo
                ->getAPIClient()
                ->post('builder/structures/' . $table . '/entry', $data);

            $pdo->setLastInsertId($response->entry_id);

            return true;
        } catch (Exception $e) {
            if ($e instanceof ClientException) {
                $response = json_decode($e->getResponse()->getBody());

                if (isset($response->error)) {
                    if (isset($response->error->errors)) {
                        foreach ($response->error->errors as $type => $messages) {
                            if (count($messages)) {
                                throw new RuntimeException(reset($messages));
                            }
                        }
                    }

                    if (isset($response->error->message)) {
                        throw new RuntimeException($response->error->message . ' (Table: entry_' . $table . ')', $e->getCode(), $e);
                    }
                }

                throw new RuntimeException($e->getMessage(), $e->getCode());
            }

            return false;
        }
    }

    public function update(PDOStatement $statement, array $arguments, Closure $callback): bool
    {
        $id = $arguments['id'];
        $data = $arguments['payload'] ?? [];

        if (!isset($data['revision_publish'])) {
            $data['revision_publish'] = true;
        }

        try {
            $statement
                ->getPDO()
                ->getAPIClient()
                ->put('builder/structures/entry/' . $id, $data);

            return true;
        } catch (Exception $e) {
            throw new PDOException($e->getMessage(), $e->getCode());
        }
    }

    public function delete(PDOStatement $statement, array $arguments, Closure $callback): bool
    {
        $id = $arguments['id'];

        try {
            $statement
                ->getPDO()
                ->getAPIClient()
                ->delete('builder/structures/entry/' . $id);

            return true;
        } catch (Exception $e) {
            throw new PDOException($e->getMessage(), $e->getCode());
        }
    }

    public function tableExists(PDOStatement $statement, array $arguments, Closure $callback): bool
    {
        $table = $arguments['table'];
        $client = $statement->getPDO()->getAPIClient();

        try {
            $result = $client->get('builder/structures/' . $table);

            if ($result !== null && isset($result->id)) {
                return true;
            }

            return false;
        } catch (ClientException $e) {
            if ($e->getResponse()->getStatusCode() === 404) {
                return false;
            }

            $statement->errorCode = $e->getCode();
            $statement->errorInfo = [$e->getCode(), $e->getCode(), $e->getMessage()];

            throw new PDOException($e->getMessage(), $e->getCode());
        }
    }

    public function createTable(PDOStatement $statement, array $arguments, Closure $callback): bool
    {
        if (!$this->tableExists($statement, $arguments, $callback)) {
            $table = $arguments['table'];
            $arguments['alias'] = $table;
            unset($arguments['table']);

            $client = $statement->getPDO()->getAPIClient();

            try {
                $client->post('builder/structures', $arguments);
            } catch (Exception $e) {
                $statement->errorCode = $e->getCode();
                $statement->errorInfo = [$e->getCode(), $e->getCode(), $e->getMessage()];

                throw new PDOException($e->getMessage(), $e->getCode());
            }
        }

        return true;
    }

    public function dropTable(PDOStatement $statement, array $arguments, Closure $callback): bool
    {
        $client = $statement->getPDO()->getAPIClient();

        try {
            $client->delete('builder/structures/' . $arguments['table']);
        } catch (Exception $e) {
            return false;
        }

        return true;
    }

    public function selectColumns(PDOStatement $statement, array $arguments, Closure $callback): bool
    {
        $table = $arguments['table'];
        $client = $statement->getPDO()->getAPIClient();

        try {
            $parentFields = [];

            parent::selectColumns($statement, $arguments, function ($fields) use (&$parentFields) {
                $parentFields = $fields;
            });

            $result = array_merge(
                $parentFields,
                array_map(
                    fn ($field) => Column::mapField($field),
                    $client->get('builder/structures/' . $table . '/fields', true)
                )
            );

            $callback(array_map(fn (Column $field) => $field->toArray(), $result));
            return true;
        } catch (Exception $e) {
            return false;
        }
    }

    public function columnExists(PDOStatement $statement, array $arguments, Closure $callback): bool
    {
        $pdo = $statement->getPDO();
        $client = $pdo->getAPIClient();

        try {
            $fields = $client->get('builder/structures/' . $arguments['table'] . '/fields');;

            foreach ($fields as $field) {
                if ($field->alias === $arguments['column']) {
                    return true;
                }
            }
        } catch (Exception $e) {
            throw new PDOException($e->getMessage(), $e->getCode());
        }

        return false;
    }


    public function addColumn(PDOStatement $statement, array $arguments, Closure $callback): bool
    {
        $pdo = $statement->getPDO();
        $client = $pdo->getAPIClient();

        $fieldExistsStatement = $pdo->prepare(
            [
                'command' => Command::TABLE_COLUMN_EXISTS,
                'arguments' => $arguments
            ]
        );

        if (!$fieldExistsStatement->execute()) {
            $table = $arguments['table'];
            unset($arguments['structure']);

            $arguments['alias'] = $arguments['column'];
            unset($arguments['column']);

            $arguments['type'] = $arguments['type'] ?? 'text';
            $arguments['type'] = Column::mapType($arguments['type']);

            try {
                $client->post('builder/structures/' . $table . '/field', $arguments);
            } catch (Exception $e) {
                throw new PDOException($e->getMessage(), $e->getCode());
            }
        }

        return true;
    }

    public function alterColumn(PDOStatement $statement, array $arguments, Closure $callback): bool
    {
        $client = $statement->getPDO()->getAPIClient();
        $fields = $client->get('builder/structures/' . $arguments['table'] . '/fields');

        foreach ($fields as $field) {
            if ($field->alias === $arguments['from']) {
                $client->put('builder/structures/field/' . $field->id, [
                    'name' => $arguments['name'],
                    'alias' => $arguments['to']
                ]);

                return true;
            }
        }

        return false;
    }

    public function dropColumn(PDOStatement $statement, array $arguments, Closure $callback): bool
    {
        $pdo = $statement->getPDO();
        $client = $pdo->getAPIClient();

        $fieldsStatement = $pdo->prepare(
            [
                'command' => Command::TABLE_COLUMNS_SELECT,
                'arguments' => $arguments
            ]
        );

        try {
            $fieldsStatement->execute();
            $fields = $fieldsStatement->fetchAll();

            foreach ($fields as $field) {
                if ($field['column'] === $arguments['column']) {
                    try {
                        $client->delete('builder/structures/field/' . $field['id']);
                    } catch (Exception $e) {
                        throw new PDOException($e->getMessage(), $e->getCode());
                    }
                }
            }
        } catch (Exception $e) {
            if ($e instanceof PDOException) {
                throw $e;
            }

            throw new PDOException($e->getMessage(), $e->getCode());
        }

        return true;
    }
}
