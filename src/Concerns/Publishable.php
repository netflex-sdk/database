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

    /**
     * @param 
     */
    public function scopePublished(Builder $query, bool $published = true, ?DateTimeInterface $at = null)
    {
        return with(new PublishedScope($published, $at))
            ->apply($query->withoutGlobalScope(PublishedScope::class), $this);
    }

    public function scopeUnpublished(Builder $query, ?DateTimeInterface $at = null)
    {
        return $this->scopePublished($query, false, $at);
    }
}
