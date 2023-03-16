<?php

namespace Netflex\Database\Observers;

use Illuminate\Support\Str;

use Netflex\Database\Model;
use Netflex\Database\Concerns\GeneratesName;

class NameObserver
{
    /**
     * @param Model&GeneratesName $model
     */
    public function saving($model)
    {
        if (!isset($model->name)) {
            $model->name = $model->freshName();
        }
    }
}
