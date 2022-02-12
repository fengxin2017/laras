<?php

namespace App\Models;

use Laras\Contracts\Auth\Authenticatable as AuthenticatableContract;
use Laras\Database\Model;
use Laras\Support\Traits\Auth\Authenticatable;

class User extends Model implements AuthenticatableContract
{
    use Authenticatable;

    /**
     * @var string
     */
    protected $table = 'user';
}