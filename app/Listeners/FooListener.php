<?php

namespace App\Listeners;

use App\Events\Foo;

class FooListener
{
    public function handle(Foo $foo)
    {
        var_dump(sprintf('this is foo event. name is %s', $foo->name));
    }
}