<?php

namespace Touhidurabir\EloquentWherelike\Tests\App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class User extends Model {

    use SoftDeletes;

    /**
     * The model associated table
     *
     * @var string
     */
    protected $table = 'users';


    /**
     * The attributes that are not mass assignable.
     *
     * @var array
     */
    protected $guarded = [];

}