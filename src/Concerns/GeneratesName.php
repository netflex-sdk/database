<?php

namespace Netflex\Database\Concerns;

use Illuminate\Support\Str;
use Netflex\Database\Observers\NameObserver;

/**
 * @property string $name
 * @property string $url
 */
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

    public function freshUrl()
    {
        return Str::slug($this->name);
    }
}
