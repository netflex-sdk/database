<?php

namespace Netflex\Database\Observers;

use Netflex\Database\Model;

class RevisionPublishObserver
{
    /**
     * @param Model $model
     */
    public function saving($model)
    {
        if (!isset($model->revision_publish)) {
            $model->revision_publish = true;
        }
    }
}
