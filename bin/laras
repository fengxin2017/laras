#!/usr/bin/env php
<?php

use Laras\Composer\ClassLoader;
use Symfony\Component\Console\Application;

ini_set('display_errors', 'on');
ini_set('display_startup_errors', 'on');

error_reporting(E_ALL ^ E_DEPRECATED);
date_default_timezone_set('Asia/Shanghai');

defined('ROOT_PATH') or define('ROOT_PATH', realpath(__DIR__ . '/../'));

require ROOT_PATH . '/vendor/autoload.php';

(function () {
    ClassLoader::init();
    $commands = require ROOT_PATH . DIRECTORY_SEPARATOR . '/config/commands.php';
    $application = new Application();
    foreach ($commands as $command) {
        $application->add(new $command);
    }
    $application->run();
})();




