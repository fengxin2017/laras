<?php

namespace App\Models;

use MoneyMaker\Contracts\Auth\Authenticatable as AuthenticatableContract;
use MoneyMaker\Database\Model;
use MoneyMaker\Support\Traits\Auth\Authenticatable;

class User extends Model implements AuthenticatableContract
{
    use Authenticatable;

    /**
     * @var string
     */
    protected $table = 'users';
}