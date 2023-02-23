<?php

namespace Netflex\Database\Observers;

use Netflex\Database\Model;

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
