<?php

namespace Netflex\Database\Concerns;

use Netflex\Database\Observers\NameObserver;

trait GeneratesName
{
    public static function bootGeneratesName()
    {
        static::observe(new NameObserver);
    }
}
