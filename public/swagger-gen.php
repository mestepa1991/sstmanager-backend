<?php


error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/../vendor/autoload.php';
use OpenApi\Generator;
$openapi = Generator::scan([
    __DIR__ . '/../src',
    __DIR__ . '/../public'
]);

header('Content-Type: application/x-yaml');
echo $openapi->toYaml();