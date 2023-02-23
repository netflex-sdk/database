<?php

namespace Netflex\Database\Concerns;

use Netflex\Database\Observers\RevisionPublishObserver;

trait PublishesRevisions
{
    public static function bootPublishesRevisions()
    {
        static::observe(new RevisionPublishObserver);
    }
}
