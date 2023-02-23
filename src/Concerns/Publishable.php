<?php

namespace Netflex\Database\Concerns;

use DateTimeInterface;
use Illuminate\Database\Eloquent\Builder;
use Netflex\Database\Scopes\PublishedScope;

trait Publishable
{
    public static function bootPublishable()
    {
        static::addGlobalScope(new PublishedScope);
    }

    public function scopeWherePublished(Builder $query, ?DateTimeInterface $at = null, bool $published = true)
    {
        return with(new PublishedScope($published, $at))
            ->apply($query->withoutGlobalScope(PublishedScope::class), $this);
    }

    public function scopeWhereUnpublished(Builder $query, ?DateTimeInterface $at = null)
    {
        return $this->scopeWherePublished($query, $at, false);
    }

    public function scopeWithUnpublished(Builder $query)
    {
        return $query->withoutGlobalScope(PublishedScope::class);
    }
}
