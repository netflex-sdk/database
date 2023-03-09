<?php

namespace Netflex\Database\Adapters;

use Exception;
use GuzzleHttp\Exception\ClientException;
use Illuminate\Support\Str;

use Netflex\Database\Driver\PDO;
use Netflex\Database\Contracts\DatabaseAdapter;
use RuntimeException;

final class EntryAdapter implements DatabaseAdapter
{
    public function insert(PDO $connection, array $data, $relation = null): bool
    {
        if (!isset($data['revision_publish'])) {
            $data['revision_publish'] = true;
        }

        try {
            $connection->setLastInsertId(null);

            $response = $connection
                ->getAPIClient()
                ->post('builder/structures/' . $relation . '/entry', $data);

            $connection->setLastInsertId($response->entry_id);

            return true;
        } catch (Exception $e) {
            if ($e instanceof ClientException) {
                $response = json_decode($e->getResponse()->getBody());

                if (isset($response->error)) {
                    foreach ($response->error->errors as $type => $messages) {
                        if (count($messages)) {
                            throw new RuntimeException(reset($messages));
                        }
                    }
                }
            }
            return false;
        }
    }

    public function update(PDO $connection, int $id, array $data, $relation = null): bool
    {
        if (!isset($data['revision_publish'])) {
            $data['revision_publish'] = true;
        }

        try {
            $connection
                ->getAPIClient()
                ->put('builder/structures/entry/' . $id, $data);

            return true;
        } catch (Exception $e) {
            return false;
        }
    }

    public function delete(PDO $connection, int $id, $relation = null): bool
    {
        try {
            $connection
                ->getAPIClient()
                ->delete('builder/structures/entry/' . $id);

            return true;
        } catch (Exception $e) {
            return false;
        }
    }
}
