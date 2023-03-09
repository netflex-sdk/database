<?php

namespace Netflex\Database\Adapters;

use Exception;

use Illuminate\Support\Str;

use Netflex\Database\Driver\PDO;
use Netflex\Database\Contracts\DatabaseAdapter;

final class EntryAdapter implements DatabaseAdapter
{
    public function insert(PDO $connection, array $data, $relation = null): bool
    {
        if (!isset($data['name'])) {
            $data['name'] = (string) Str::uuid();
        }

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
