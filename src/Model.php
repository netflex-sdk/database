<?php

namespace Netflex\Database;

use Illuminate\Database\Eloquent\Model as EloquentModel;
use Netflex\Database\Concerns\GeneratesName;
use Netflex\Database\Concerns\Publishable;
use Netflex\Database\Concerns\PublishesRevisions;

abstract class Model extends EloquentModel
{
    use GeneratesName;
    use Publishable;
    use PublishesRevisions;

    const CREATED_AT = 'created';
    const UPDATED_AT = 'updated';
}
