<?php

namespace Netflex\Database\Observers;

use Illuminate\Support\Str;
use Netflex\Database\Model;

class NameObserver
{
    /**
     * @param Model $model
     */
    public function saving($model)
    {
        if (!$model->name) {
            $model->name = Str::uuid();
        }
    }
}
