<?php

namespace Netflex\Database;

use Illuminate\Database\Eloquent\Model as EloquentModel;

use Netflex\Database\Concerns\GeneratesName;
use Netflex\Database\Concerns\Publishable;
use Netflex\Database\Concerns\PublishesRevisions;
use Netflex\Database\Concerns\RefreshesOnSave;

abstract class Model extends EloquentModel
{
    use GeneratesName;
    use Publishable;
    use PublishesRevisions;
    use RefreshesOnSave;

    const CREATED_AT = 'created';
    const UPDATED_AT = 'updated';
}
