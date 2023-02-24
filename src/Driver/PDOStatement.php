<?php

namespace Netflex\Database\Driver;

use Exception;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\ServerException;

use PDOException;
use PDOStatement as BasePDOStatement;

use Illuminate\Support\Str;
use Netflex\Database\Driver\Schema\Field;

final class PDOStatement extends BasePDOStatement
{
    protected PDO $pdo;
    protected $statement = null;
    protected ?array $result = null;

    protected $errorCode = null;
    protected $errorInfo = [];

    public $affectedRows = 0;

    public function __construct(PDO $pdo, $statement = null)
    {
        $this->pdo = $pdo;
        $this->statement = $statement;
    }

    public function errorCode(): ?string
    {
        return $this->errorCode;
    }

    public function errorInfo(): array
    {
        return $this->errorInfo;
    }

    public function fetch($how = null, $orientation = null, $offset = null)
    {
        if ($this->result) {
            if (!isset($this->result['hits'])) {
                $result = $this->result;
                $this->result = null;

                return $result;
            }

            if ($row = array_shift($this->result['hits']['hits'])) {
                return $row['_source'];
            }
        }

        return false;
    }

    public function fetchAll($mode = PDO::FETCH_BOTH, $class_name = null, $ctor_args = null): array
    {
        if ($this->result) {
            if (!isset($this->result['hits'])) {
                $result = $this->result;
                $this->result = null;

                return $result;
            }

            if (count($this->result['hits']['hits']) > 0) {
                $rows = array_map(function ($row) {
                    return $row['_source'];
                }, $this->result['hits']['hits']);

                $this->result = null;

                return $rows;
            }
        }

        return [];
    }

    public function fetchColumn($column = 0): mixed
    {
        if (!count($this->result)) {
            return false;
        }

        $row = $this->result[0] ?? [];
        $keys = array_keys($row);

        if (isset($row[$keys[$column]])) {
            return $row[$keys[$column]];
        }

        return null;
    }

    public function bindValue($parameter, $value, $data_type = PDO::PARAM_STR): bool
    {
        return false;
    }

    protected function performApiAction($action, $request)
    {
        $command = 'execute' . Str::camel($action);

        if (method_exists($this, $command)) {
            return $this->$command($request);
        }

        throw new PDOException('Unsupported command [' . $action . '].');
    }

    protected function executeCreateStructureField($request)
    {
        if (!$this->executeStructureFieldExists($request)) {
            $structure = $request['structure'];
            unset($request['structure']);

            $client = $this->pdo->getAPIClient();

            try {
                $client->post('builder/structures/' . $structure . '/field', $request);
            } catch (Exception $e) {
                throw new PDOException($e->getMessage(), $e->getCode());
            }
        }

        return true;
    }

    protected function executeRenameStructureField($request)
    {
        $client = $this->pdo->getAPIClient();
        $fields = $client->get('builder/structures/' . $request['structure'] . '/fields');

        foreach ($fields as $field) {
            if ($field->alias === $request['from']) {
                $client->put('builder/structures/field/' . $field->id, [
                    'name' => $request['name'],
                    'alias' => $request['to']
                ]);

                return true;
            }
        }

        return false;
    }

    protected function executeDeleteStructureField($request)
    {
        $client = $this->pdo->getAPIClient();
        $fields = $client->get('builder/structures/' . $request['structure'] . '/fields');

        foreach ($fields as $field) {
            if ($field->alias === $request['alias']) {
                $client->delete('builder/structures/field/' . $field->id);

                return true;
            }
        }

        return false;
    }

    protected function executeDeleteStructureFieldIfExists($request)
    {
        $this->executeStructureFieldExists($request);

        return true;
    }

    protected function executeDeleteStructure($request)
    {
        $client = $this->pdo->getAPIClient();

        try {
            $client->delete('builder/structures/' . $request['alias']);
        } catch (Exception $e) {
            return false;
        }

        return true;
    }

    protected function executeDeleteStructureIfExists($request)
    {
        if ($this->executeStructureExists($request)) {
            return $this->executeDeleteStructure($request);
        }

        return true;
    }

    protected function executeStructureExists($request)
    {
        $client = $this->pdo->getAPIClient();

        try {
            $result = $client->get('builder/structures/' . $request['alias']);

            if ($result && isset($result->id)) {
                return true;
            }

            return false;
        } catch (ClientException $e) {
            if ($e->getResponse()->getStatusCode() === 404) {
                return false;
            }

            $this->errorCode = $e->getCode();
            $this->errorInfo = [$e->getCode(), $e->getCode(), $e->getMessage()];

            return false;
        }
    }

    protected function executeStructureFieldExists($request)
    {
        $client = $this->pdo->getAPIClient();
        $structure = $request['structure'];

        try {
            $fields = $client->get('builder/structures/' . $structure . '/fields');

            foreach ($fields as $field) {
                if ($field->alias === $request['alias']) {
                    return true;
                }
            }
        } catch (Exception $e) {
            throw new PDOException($e->getMessage(), $e->getCode());
        }

        return false;
    }

    protected function executeCreateStructure($request)
    {
        if (!$this->executeStructureExists($request)) {
            $client = $this->pdo->getAPIClient();

            try {
                $client->post('builder/structures', $request);
            } catch (Exception $e) {
                $this->errorCode = $e->getCode();
                $this->errorInfo = [$e->getCode(), $e->getCode(), $e->getMessage()];

                return false;
            }
        }

        return true;
    }

    protected function executeListFields($request)
    {
        try {
            $this->result = array_values(
                array_unique([
                    ...Field::RESERVED_FIELDS,
                    ...array_map(
                        fn ($field) => $field->alias,
                        $this->pdo
                            ->getAPIClient()
                            ->get('builder/structures/' . $request['structure'] . '/fields')
                    )
                ])
            );
        } catch (Exception $e) {
            $this->errorCode = $e->getCode();
            $this->result = null;

            throw new PDOException($e->getMessage(), $e->getCode(), $e);
        }

        return true;
    }

    protected function executeSearch($request)
    {
        try {
            $result = $this->pdo
                ->getAPIClient()
                ->post('search/raw', $request, true);

            $this->affectedRows = $result['hits']['total'] ?? 0;

            if (isset($result['aggregations'])) {
                $aggregations = $result['aggregations'];

                if (isset($aggregations['aggregate']) && array_key_exists('value', $aggregations['aggregate'])) {
                    $aggregations['aggregate'] = $aggregations['aggregate']['value'];
                }

                $result['hits']['hits'][] = [
                    '_source' => $aggregations
                ];
            }

            if (isset($result['hits']['hits'])) {
                $this->affectedRows = count($result['hits']['hits']);
            }

            $this->result = $result;

            return true;
        } catch (ServerException $e) {
            $response = $e->getResponse();
            $code = $response->getStatusCode();
            $message = $e->getMessage();

            if ($esResponse = json_decode($response->getBody())) {
                if ($esResponse->error) {
                    if ($esError = json_decode($esResponse->error->message)) {
                        $message = $esError->error->root_cause[0]->reason;
                    }
                }
            }

            $this->errorCode = $code;
            $this->errorInfo = [get_class($e), $code, $message];
            $this->result = null;

            throw new PDOException($message, $code, $e);
        }
    }

    public function execute($params = null): bool
    {
        $this->affectedRows = 0;

        if (isset($this->statement['command'])) {
            $command = $this->statement['command'];
            $arguments = $this->statement['arguments'] ?? [];

            try {
                if ($result = $this->performApiAction($command, $arguments)) {
                    return $result;
                }

                throw new PDOException('Failed to execute statement', $this->errorCode ?? null);
            } catch (Exception $e) {
                if ($e instanceof PDOException) {
                    throw $e;
                }

                $this->errorCode = $e->getCode();
                $this->errorInfo = [$e->getCode(), $e->getCode(), $e->getMessage()];

                throw new PDOException($e->getMessage(), $e->getCode(), $e);
            }
        }

        return false;
    }
}
