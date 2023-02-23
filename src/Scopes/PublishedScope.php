<?php

namespace Netflex\Database\Scopes;

use DateTimeInterface;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;
use Illuminate\Support\Carbon;

class PublishedScope implements Scope
{
    protected bool $published;
    protected DateTimeInterface $now;

    public function __construct(bool $published = true, ?DateTimeInterface $now = null)
    {
        $this->published = $published;
        $this->now = $now ?? Carbon::now();
    }

    public function getPublished(): bool
    {
        return $this->published;
    }

    public function getNow(): DateTimeInterface
    {
        return $this->now ?? Carbon::now();
    }

    /**
     * Apply the scope to a given Eloquent query builder.
     */
    public function apply(Builder $builder, Model $model): void
    {
        $now = $this->getNow();
        $published = $this->getPublished();

        $builder
            ->where('published', $published)
            ->where(
                fn (Builder $query) => $query
                    ->where('use_time', false)
                    ->orWhere(fn (Builder $query) => $query
                        ->where('use_time', true)
                        ->where(
                            fn (Builder $query) => $query
                                ->whereNull('start')
                                ->orWhere('start', '<=', $now)
                                ->whereNull('stop')
                                ->orWhere('stop', '>=', $now)
                        ))
            );
    }
}
