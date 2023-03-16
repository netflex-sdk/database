<?php

namespace Netflex\Database\Eloquent;

use Illuminate\Database\Eloquent\Model as EloquentModel;

use Netflex\Database\Concerns\Publishable;
use Netflex\Database\Concerns\GeneratesName;
use Netflex\Database\Concerns\RefreshesOnSave;

abstract class Model extends EloquentModel
{
    use Publishable;
    use GeneratesName;
    use RefreshesOnSave;

    const CREATED_AT = 'created';
    const UPDATED_AT = 'updated';
}
