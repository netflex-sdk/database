<?php

namespace Netflex\Database\Concerns;

use Illuminate\Support\Str;
use Netflex\Database\Observers\NameObserver;

trait GeneratesName
{
    public static function bootGeneratesName()
    {
        static::observe(new NameObserver);
    }

    public function freshName()
    {
        return (string) Str::uuid();
    }
}
