<?php

use \MyRadio\Config;
use \MyRadio\MyRadioException;
use \MyRadio\MyRadio\CoreUtils;
use \MyRadio\ServiceAPI\MyRadio_Swagger2;

header('Content-Type: application/json');

//Strip everything from the URL before the version and query string
$url = explode('?', explode(Config::$api_uri.'v2/', $_SERVER['REQUEST_URI'])[1])[0];

if ($url === 'swagger.json') {
    echo json_encode(MyRadio_Swagger2::resources());

    return;
}

$parts = explode('/', $url);
$op = strtolower($_SERVER['REQUEST_METHOD']);
$class = preg_replace('/[^0-9a-zA-Z-_]+/', '', $parts[0]);
$method = preg_replace('/[^0-9a-zA-Z-_]+/', '', $parts[sizeof($parts) - 1]);
$id = null;
$arg0 = null;
if (sizeof($parts) === 3) {
    if (is_numeric($parts[1])) {
        $id = $parts[1];
    } else {
        $method = $parts[1];
        $arg0 = $parts[2];
    }
} elseif (sizeof($parts) === 4) {
    $id = $parts[1];
    $method = $parts[2];
    $arg0 = $parts[3];
} elseif (is_numeric($method)) {
    $id = $method;
    $method = null;
}

try {
    $response = MyRadio_Swagger2::handleRequest($op, $class, $method, $id, $arg0);
    $data = [
        'status' => 'OK',
        'payload' => CoreUtils::dataSourceParser($response['content'], $response['mixins']),
        'time' => sprintf('%f', $__start + microtime(true)),
    ];
} catch (MyRadioException $e) {
    header('HTTP/1.1 '.$e->getCode().' '.$e->getCodeName());
    $data = [
        'status' => 'FAIL',
        'payload' => $e->getMessage(),
        'time' => sprintf('%f', $__start + microtime(true)),
    ];
}

echo json_encode($data);
