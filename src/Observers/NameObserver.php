<?php

namespace Netflex\Database\Observers;

use Illuminate\Database\Eloquent\Model;
use Netflex\Database\Contracts\NameGenerator;

class NameObserver
{
    /** @param Model&NameGenerator $model */
    protected function setModelName($model)
    {
        if (!isset($model->name)) {
            $model->name = $model->freshName();
        }
    }

    /** @param Model&NameGenerator $model */
    protected function setModelUrl($model)
    {
        if (!isset($model->url)) {
            $model->url = $model->freshUrl();
        }
    }

    /**
     * @param Model&NameGenerator $model
     */
    public function saving($model)
    {
        $this->setModelName($model);
    }

    /**
     * @param Model&NameGenerator $model
     */
    public function creating($model)
    {
        $this->setModelName($model);
        $this->setModelUrl($model);
    }
}
