<?php

use App\Commands\KeyGenerateCommand;
use App\Commands\TestCommand;
use Laras\Commands\Portal;

return [
    Portal::class,
    TestCommand::class,
    KeyGenerateCommand::class
];