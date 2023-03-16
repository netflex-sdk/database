<?php

namespace Netflex\Database\Concerns;

use DateTimeInterface;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;

use Netflex\Database\Scopes\PublishedScope;

trait Publishable
{
    public static function bootPublishable()
    {
        static::addGlobalScope(new PublishedScope);
    }

    public function scopePublished(Builder $query, ?DateTimeInterface $at = null, bool $published = true)
    {
        return with(new PublishedScope($published, $at))
            ->apply($query->withoutGlobalScope(PublishedScope::class), $this);
    }

    public function scopeUnpublished(Builder $query, ?DateTimeInterface $at = null)
    {
        return $this->scopePublished($query, $at, false);
    }

    public function scopeWithUnpublished(Builder $query)
    {
        return $query->withoutGlobalScope(PublishedScope::class);
    }

    /** @return bool */
    public function publish()
    {
        /** @var Model $this */
        $this->published = true;

        return $this->save();
    }

    /** @return bool */
    public function unpublish()
    {
        /** @var Model $this */
        $this->published = false;

        return $this->save();
    }
}
