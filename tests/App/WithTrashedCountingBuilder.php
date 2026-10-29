<?php

namespace Touhidurabir\EloquentWherelike\Tests\App;

use Illuminate\Database\Eloquent\Builder;

class WithTrashedCountingBuilder extends Builder
{
    /**
     * Number of times withTrashed() has been called on any instance
     *
     * @var int
     */
    public static $withTrashedCalls = 0;


    /**
     * Count the withTrashed() macro calls, then forward as usual.
     *
     * @param  string  $method
     * @param  array   $parameters
     * @return mixed
     */
    public function __call($method, $parameters)
    {

        if ($method === 'withTrashed') {

            static::$withTrashedCalls++;
        }

        return parent::__call($method, $parameters);
    }
}
