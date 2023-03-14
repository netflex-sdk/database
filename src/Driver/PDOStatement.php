<?php

namespace Netflex\Database\Driver;

use Exception;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\ServerException;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Log;
use PDOException;
use PDOStatement as BasePDOStatement;

use Illuminate\Support\Str;
use Netflex\Database\Contracts\DatabaseAdapter;
use Netflex\Database\Driver\Schema\Field;

final class PDOStatement extends BasePDOStatement
{
    protected PDO $pdo;
    protected DatabaseAdapter $adapter;

    protected $statement = null;
    protected ?array $result = null;

    public $errorCode = null;
    public $errorInfo = [];

    public $affectedRows = 0;

    public function __construct(PDO $pdo, $statement = null)
    {
        $this->pdo = $pdo;
        $this->statement = $statement;
        $this->adapter = $this->pdo->getAdapter();
    }

    public function getPDO(): PDO
    {
        return $this->pdo;
    }

    public function getAdapter(): DatabaseAdapter
    {
        return $this->adapter;
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

    protected function performAction($action, $request)
    {
        $adapter = $this->getAdapter();

        if (method_exists($adapter, $action)) {
            return call_user_func_array([$adapter, $action], [$this, $request, function ($result) {
                $this->result = $result;
            }]);
        }

        throw new PDOException('Unsupported command [' . $action . '].');
    }

    public function execute($params = null): bool
    {
        $this->affectedRows = 0;

        if (isset($this->statement['command'])) {
            $command = $this->statement['command'];
            $arguments = $this->statement['arguments'] ?? [];

            try {
                $result = $this->performAction($command, $arguments);

                if ($result !== null) {
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
