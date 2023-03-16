<?php

namespace Netflex\Database\Driver;

use Netflex\Database\DBAL\Column;
use Illuminate\Database\Query\Processors\Processor;

class PostProcessor extends Processor
{
    public function processColumnListing($results)
    {
        return array_map(
            fn (Column $column) => $column->name(),
            $results
        );
    }
}
