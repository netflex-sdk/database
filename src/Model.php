<?php

namespace Netflex\Database;

use Illuminate\Database\Eloquent\Model as EloquentModel;

use Netflex\Database\Concerns\RefreshesOnSave;

abstract class Model extends EloquentModel
{
    use RefreshesOnSave;

    const CREATED_AT = 'created';
    const UPDATED_AT = 'updated';
}
