<?php

namespace Touhidurabir\EloquentWherelike\Tests\App;

class TrackedProfile extends Profile
{
    /**
     * Create a new Eloquent query builder for the model.
     *
     * @param  \Illuminate\Database\Query\Builder  $query
     * @return \Touhidurabir\EloquentWherelike\Tests\App\WithTrashedCountingBuilder
     */
    public function newEloquentBuilder($query)
    {

        return new WithTrashedCountingBuilder($query);
    }
}
