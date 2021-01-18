<?php

namespace App\Events;

use Laras\Foundation\Events\Dispatchable;
use Laras\Support\Event;

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