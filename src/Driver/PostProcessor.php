<?php

namespace Netflex\Database\Driver;

use Illuminate\Database\Query\Processors\Processor;
use Illuminate\Support\Collection;

class PostProcessor extends Processor
{
    public function processColumnListing($results)
    {
        return Collection::make($results)
            ->pluck('column')
            ->all();
    }
}
