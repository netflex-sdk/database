<?php

namespace Netflex\Database\Concerns;

use Netflex\Database\Observers\RefreshOnSaveObserver;

trait RefreshesOnSave
{
    public static function bootRefreshesOnSave()
    {
        static::observe(new RefreshOnSaveObserver);
    }
}
