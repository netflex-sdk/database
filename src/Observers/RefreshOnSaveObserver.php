<?php

namespace Netflex\Database\Observers;

use Illuminate\Database\Eloquent\Model;

class RefreshOnSaveObserver
{
    /**
     * @param Model $model
     */
    public function saved($model)
    {
        $model->setRawAttributes($model->fresh()->getAttributes());
    }
}
