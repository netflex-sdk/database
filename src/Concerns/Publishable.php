<?php

namespace Netflex\Database\Concerns;

use DateTimeInterface;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;

use Illuminate\Support\Carbon;

use Netflex\Database\Scopes\PublishedScope;

/**
 * @property bool $published
 * @property bool $use_time
 * @property Carbon|null $start
 * @property Carbon|null $end
 * @method Builder<static> scopePublished(Builder $query, DateTimeInterface|null $at = null, bool $published = true)
 * @method Builder<static> scopeUnpublished(Builder $query, DateTimeInterface|null $at = null)
 * @method Builder<static> scopeWithUnpublished(Builder $query)
 * @method static Builder<static> published(DateTimeInterface|null $at = null, bool $published = true)
 * @method static Builder<static> unpublished(DateTimeInterface|null $at = null)
 * @method static Builder<static> withUnpublished()
 */
trait Publishable
{
    public static function bootPublishable()
    {
        static::addGlobalScope(new PublishedScope);
    }

    public function initializePublishable()
    {
        /** @var Model $this */

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
        /** @var Model $this */

        $this->setAttribute('published', true);

        return $this->save();
    }

    /** @return bool */
    public function unpublish()
    {
        /** @var Model $this */

        $this->setAttribute('published', false);

        return $this->save();
    }
}
