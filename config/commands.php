<?php

use App\Commands\KeyGenerateCommand;
use App\Commands\TestCommand;
use Laras\Commands\Start;

return [
    Start::class,
    TestCommand::class,
    KeyGenerateCommand::class
];