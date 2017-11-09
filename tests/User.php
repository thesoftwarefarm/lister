<?php

namespace TsfCorp\Lister\Test;

use Illuminate\Database\Eloquent\Model;

class User extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = ['email', 'name'];

    protected $table = 'users';

    public $timestamps = false;
}