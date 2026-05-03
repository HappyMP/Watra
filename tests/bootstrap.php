<?php

use Symfony\Component\Dotenv\Dotenv;

require dirname(__DIR__).'/vendor/autoload.php';

// Force test environment when running PHPUnit, regardless of container APP_ENV
$_SERVER['APP_ENV'] = $_ENV['APP_ENV'] = 'test';
putenv('APP_ENV=test');

(new Dotenv())->bootEnv(dirname(__DIR__).'/.env');

if ($_SERVER['APP_DEBUG']) {
    umask(0000);
}
