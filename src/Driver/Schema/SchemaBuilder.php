<?php

namespace Netflex\Database\Driver\Schema;

use Illuminate\Database\Schema\Builder;

use Netflex\Database\Driver\Connection;
use Netflex\Database\Driver\Schema\SchemaGrammar;

class SchemaBuilder extends Builder
{
    protected $client;

    public function __construct(Connection $connection)
    {
        if (!$connection->getSchemaGrammar()) {
            $connection->useDefaultSchemaGrammar();
        }

        parent::__construct($connection);
        $this->client = $connection->getPdo()->getAPIClient();
        $this->grammar = new SchemaGrammar($connection);
    }

    public function hasTable($table)
    {
        $result = $this->client->get('builder/structures');

        foreach ($result as $structure) {
            if ($structure->id == $table || $structure->alias == $table) {
                return true;
            }
        }

        return false;
    }
}
