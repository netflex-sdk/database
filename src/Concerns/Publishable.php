<?php

namespace Netflex\Database\Concerns;

use DateTimeInterface;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;

use Netflex\Database\Scopes\PublishedScope;

/**
 * @property bool $published
 * @property bool $use_time
 * @property \Illuminate\Support\Carbon|null $start
 * @property \Illuminate\Support\Carbon|null $end
 */
trait Publishable
{
    public static function bootPublishable()
    {
        static::addGlobalScope(new PublishedScope);
    }

    public function initializePublishable()
    {
        /** @var Model&Publishable $this */
        $this->casts['published'] = 'boolean';
        $this->casts['use_time'] = 'boolean';
        $this->casts['start'] = 'datetime';
        $this->casts['stop'] = 'datetime';
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
        /** @var Model&Publishable $this */

        $this->published = true;

        return $this->save();
    }

    /** @return bool */
    public function unpublish()
    {
        /** @var Model&Publishable $this */

        $this->published = false;

        return $this->save();
    }
}
