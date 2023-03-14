<?php

namespace Netflex\Database\Contracts;

use Closure;

use Netflex\Database\Driver\PDO;
use Netflex\Database\Driver\PDOStatement;
use Netflex\Database\Driver\Schema\Field;

interface DatabaseAdapter
{
    // CRUD
    public function select(PDOStatement $statement, array $arguments, Closure $closure): bool;
    public function insert(PDOStatement $statement, array $arguments, Closure $closure): bool;
    public function update(PDOStatement $statement, array $arguments, Closure $closure): bool;
    public function delete(PDOStatement $statement, array $arguments, Closure $closure): bool;

    // Table
    public function tableExists(PDOStatement $statement, array $arguments, Closure $callback): bool;
    public function createTable(PDOStatement $statement, array $arguments, Closure $callback): bool;
    public function dropTable(PDOStatement $statement, array $arguments, Closure $callback): bool;
    public function dropTableIfExists(PDOStatement $statement, array $arguments, Closure $callback): bool;

    // Columns
    public function selectColumns(PDOStatement $statement, array $arguments, Closure $callback): bool;
    public function columnExists(PDOStatement $statement, array $arguments, Closure $callback): bool;
    public function addColumn(PDOStatement $statement, array $arguments, Closure $callback): bool;
    public function alterColumn(PDOStatement $statement, array $arguments, Closure $callback): bool;
    public function dropColumn(PDOStatement $statement, array $arguments, Closure $callback): bool;
    public function dropColumnIfExists(PDOStatement $statement, array $arguments, Closure $callback): bool;
}
