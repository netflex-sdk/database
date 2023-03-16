<?php

namespace Netflex\Database\Adapters;

use Closure;
use Exception;
use PDOException;
use RuntimeException;

use GuzzleHttp\Exception\ClientException;

use Netflex\Database\DBAL\Adapters\AbstractAdapter;
use Netflex\Database\DBAL\Concerns\PerformsQueries;
use Netflex\Database\DBAL\PDOStatement;
use Netflex\Database\DBAL\Column;

final class CustomerAdapter extends AbstractAdapter
{
    use PerformsQueries;

    protected array $reservedTableNames = [
        'customer'
    ];

    protected array $reservedFields = [
        'id' => [
            'type' => 'integer',
            'notnull' => true,
            'autoincrement' => true,
            'comment' => 'Primary key'
        ],
        'extsync_id' => [
            'type' => 'integer',
            'notnull' => false,
            'comment' => 'External sync ID'
        ],
        'group_id' => [
            'type' => 'integer',
            'notnull' => false,
            'comment' => 'Group ID'
        ],
        'firstname' => [
            'type' => 'string',
            'notnull' => false,
            'comment' => 'First name'
        ],
        'surname' => [
            'type' => 'string',
            'notnull' => false,
            'comment' => 'Surname'
        ],
        'company' => [
            'type' => 'string',
            'notnull' => false,
            'comment' => 'Company'
        ],
        'companyId' => [
            'type' => 'string',
            'notnull' => false,
            'comment' => 'Company ID'
        ],
        'mail' => [
            'type' => 'string',
            'notnull' => true,
            'comment' => 'E-mail'
        ],
        'phone' => [
            'type' => 'string',
            'notnull' => false,
            'comment' => 'Phone'
        ],
        'phone_countrycode' => [
            'type' => 'string',
            'notnull' => false,
            'comment' => 'Phone country code'
        ],
        'username' => [
            'type' => 'string',
            'notnull' => false,
            'comment' => 'Username'
        ],
        'tags' => [
            'type' => 'array',
            'notnull' => false,
            'comment' => 'Tags'
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
        'user_hash' => [
            'type' => 'string',
            'notnull' => false,
            'comment' => 'User hash'
        ],
        'no_newsletter' => [
            'type' => 'boolean',
            'notnull' => false,
            'comment' => 'Newsletters'
        ],
        'no_sms' => [
            'type' => 'boolean',
            'notnull' => false,
            'comment' => 'SMS'
        ],
        'score' => [
            'type' => 'integer',
            'notnull' => false,
            'comment' => 'Score'
        ],
        'token' => [
            'type' => 'string',
            'notnull' => false,
            'comment' => 'Token'
        ],
    ];

    public function insert(PDOStatement $statement, array $arguments, Closure $callback): bool
    {
        $data = $arguments['payload'] ?? [];
        $pdo = $statement->getPDO();

        try {
            $pdo->setLastInsertId(null);

            $response = $pdo
                ->getAPIClient()
                ->post('relations/customers/customer', $data);

            $pdo->setLastInsertId($response->customer_id);

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
                        throw new RuntimeException($response->error->message . ' (Table: customer)', $e->getCode(), $e);
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

        try {
            $statement
                ->getPDO()
                ->getAPIClient()
                ->put('relations/customers/customer/' . $id, $data);

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
                ->delete('relations/customers/customer/' . $id);

            return true;
        } catch (Exception $e) {
            throw new PDOException($e->getMessage(), $e->getCode());
        }
    }

    public function tableExists(PDOStatement $statement, array $arguments, Closure $callback): bool
    {
        return true;
    }

    public function createTable(PDOStatement $statement, array $arguments, Closure $callback): bool
    {
        return true;
    }

    public function dropTable(PDOStatement $statement, array $arguments, Closure $callback): bool
    {
        return false;
    }

    public function selectColumns(PDOStatement $statement, array $arguments, Closure $callback): bool
    {
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
                    $client->get('relations/customers/fields', true)
                )
            );

            $callback(array_map(fn (Column $field) => $field->toArray(), $result));
            return true;
        } catch (Exception $e) {
            return false;
        }
    }
}
