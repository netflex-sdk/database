<?php

namespace Netflex\Database\Adapters;

use Closure;
use Exception;
use RuntimeException;
use PDOException;

use GuzzleHttp\Exception\ClientException;

use Illuminate\Support\Facades\App;
use Illuminate\Support\Str;

use Netflex\Database\DBAL\Adapters\AbstractAdapter;
use Netflex\Database\DBAL\PDOStatement;
use Netflex\Database\DBAL\Command;
use Netflex\Database\DBAL\Column;
use Netflex\Database\DBAL\Concerns\PerformsQueries;

final class EntryAdapter extends AbstractAdapter
{
    use PerformsQueries;

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
            $console = null;
            $table = $arguments['table'];

            $arguments['alias'] = $arguments['table'];
            unset($arguments['table']);

            if (App::runningInConsole()) {
                $console = new \Symfony\Component\Console\Output\ConsoleOutput();
                $console->writeln('Creating search index for structure [' . $table . ']...');
            }

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

    public function dropTableIfExists(PDOStatement $statement, array $arguments, Closure $callback): bool
    {
        if ($this->tableExists($statement, $arguments, $callback)) {
            return $this->dropTable($statement, $arguments, $callback);
        }

        return false;
    }

    public function selectColumns(PDOStatement $statement, array $arguments, Closure $callback): bool
    {
        $table = $arguments['table'];

        try {
            $result = Column::getFields($statement->getPDO()->getAPIClient(), $table);
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
                        return true;
                    } catch (Exception $e) {
                        throw new PDOException($e->getMessage(), $e->getCode());
                    }
                }
            }
        } catch (Exception $e) {
            throw new PDOException($e->getMessage(), $e->getCode());
        }
    }

    public function dropColumnIfExists(PDOStatement $statement, array $arguments, Closure $callback): bool
    {
        if ($this->columnExists($statement, $arguments, $callback)) {
            return $this->dropColumn($statement, $arguments, $callback);
        }
    }
}
