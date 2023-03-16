<?php

namespace Netflex\Database\Observers;

use Illuminate\Support\Str;

use Illuminate\Database\Eloquent\Model;
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

    /**
     * @param Model&GeneratesName $model
     */
    public function creating($model)
    {
        if (!isset($model->name)) {
            $model->name = $model->freshName();
        }

        if (!isset($model->url)) {
            $model->url = Str::slug($model->name);
        }
    }
}
