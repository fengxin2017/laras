<?php

namespace App\Jobs;

use App\Annotations\Inject;
use App\Test\Bar;
use Laras\Foundation\Bus\Job;

class FooJob extends Job
{
    /**
     * @Inject()
     * @var Bar
     */
    public $bar;

    protected $name;

    /**
     * FooJob constructor.
     * @param string $name
     */
    public function __construct(string $name)
    {
        $this->name = $name;
    }

    public function handle()
    {
        var_dump($this->bar);
        var_dump(date('Y-m-d H:i:s', time()));
        var_dump($this->name);
    }
}