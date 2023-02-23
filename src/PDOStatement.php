<?php

namespace Netflex\Database;

use Exception;
use GuzzleHttp\Exception\ServerException;
use PDOException;
use PDOStatement as BasePDOStatement;

use Netflex\Query\Builder;

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
            if ($row = array_shift($this->result['hits']['hits'])) {
                return $row['_source'];
            }
        }

        return false;
    }

    public function fetchAll($mode = PDO::FETCH_BOTH, $class_name = null, $ctor_args = null): array
    {
        if ($this->result) {
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

    public function execute($params = null): bool
    {
        /* dd($this->statement); */
        $this->affectedRows = 0;

        try {
            $result = $this->pdo
                ->getAPIClient()
                ->post('search/raw', $this->statement, true);

            $this->affectedRows = $result['hits']['total'] ?? 0;

            if (isset($result['aggregations'])) {
                $aggregations = $result['aggregations'];

                if (isset($aggregations['aggregate']) && isset($aggregations['aggregate']['value'])) {
                    $aggregations['aggregate'] = $aggregations['aggregate']['value'];
                }

                $result['hits']['hits'][] = [
                    '_source' => $aggregations
                ];
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

        return false;
    }
}
