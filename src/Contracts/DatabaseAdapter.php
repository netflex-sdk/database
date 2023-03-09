<?php

namespace Netflex\Database\Contracts;

use Netflex\Database\Driver\PDO;

interface DatabaseAdapter
{
    public function insert(PDO $connection, array $data, $relation = null): bool;
    public function update(PDO $connection, int $id, array $data, $relation = null): bool;
    public function delete(PDO $connection, int $id, $relation = null): bool;
}
