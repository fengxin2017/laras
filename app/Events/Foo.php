<?php

namespace App\Events;

use MoneyMaker\Foundation\Events\Dispatchable;
use MoneyMaker\Support\Event;

class Foo extends Event
{
    use Dispatchable;
    public $name;

    /**
     * Foo constructor.
     * @param string $name
     */
    public function __construct(string $name)
    {
        $this->name = $name;
    }
}