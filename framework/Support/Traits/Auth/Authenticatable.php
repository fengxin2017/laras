<?php


namespace MoneyMaker\Support\Traits\Auth;


trait Authenticatable
{
    /**
     * @return mixed
     */
    public function getAuthIdentifierName()
    {
        return $this->getKeyName();
    }

    /**
     * @return mixed
     */
    public function getAuthIdentifier()
    {
        return $this->{$this->getAuthIdentifierName()};
    }
}