<?php

use Symfony\Component\Dotenv\Dotenv;

require dirname(__DIR__).'/vendor/autoload.php';

$_SERVER['APP_ENV'] = $_ENV['APP_ENV'] = 'test';
$_SERVER['APP_DEBUG'] = $_ENV['APP_DEBUG'] = '1';
putenv('APP_ENV=test');
putenv('APP_DEBUG=1');

if (!isset($_SERVER['APP_SECRET'])) {
    $_SERVER['APP_SECRET'] = $_ENV['APP_SECRET'] = 'test-secret';
    putenv('APP_SECRET=test-secret');
}
if (!isset($_SERVER['DEFAULT_URI'])) {
    $_SERVER['DEFAULT_URI'] = $_ENV['DEFAULT_URI'] = 'http://localhost';
    putenv('DEFAULT_URI=http://localhost');
}

$envFile = dirname(__DIR__).'/.env';
if (file_exists($envFile)) {
    (new Dotenv())->loadEnv($envFile, 'APP_ENV', 'test');
}
