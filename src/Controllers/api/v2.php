<?php

use \MyRadio\Config;
use \MyRadio\MyRadioException;
use \MyRadio\MyRadio\CoreUtils;
use \MyRadio\ServiceAPI\MyRadio_Swagger2;

//Strip everything from the URL before the version and query string
$url = explode('?', explode(Config::$api_uri.'v2/', $_SERVER['REQUEST_URI'])[1])[0];

if ($url === 'swagger.json') {
    echo json_encode(MyRadio_Swagger2::resources());

    return;
}

// @todo: Isn't this some fun confusing spaghetti code. Need to refactor this routing.
$parts = explode('/', $url);
// Ignore trailing slashes
if ($parts[sizeof($parts) - 1] === '') {
    array_pop($parts);
}
$op = strtolower($_SERVER['REQUEST_METHOD']);
$class = preg_replace('/[^0-9a-zA-Z-_]+/', '', $parts[0]);
// Defaults to assuming the last field
$method = preg_replace('/[^0-9a-zA-Z-_]+/', '', $parts[sizeof($parts) - 1]);
$id = null;
$arg0 = null;
if (sizeof($parts) === 1) {
    $method = null;
} elseif (sizeof($parts) === 3) {
    /**
     * Possible combinations here are /class/id/method/
     * and /class/method/arg0
     * Check which combination is a valid endpoint
     */
    if (MyRadio_Swagger2::isValidClassMethodCombination($class, $method)) {
        $id = $parts[1];
    } else {
        $method = $parts[1];
        $arg0 = $parts[2];
    }
} elseif (sizeof($parts) === 4) {
    $id = $parts[1];
    $method = $parts[2];
    $arg0 = $parts[3];
} elseif (!MyRadio_Swagger2::isValidClassMethodCombination($class, $method)) {
    // If it just wants the toDataSource, $method here could be the ID
    $id = $method;
    $method = null;
}

try {
    $response = MyRadio_Swagger2::handleRequest($op, $class, $method, $id, $arg0);
    if ($response['status']) {
        header('HTTP/1.1 ' . $response['status']);
    }
    header('Content-Type: application/json');
    $data = [
        'status' => 'OK',
        'payload' => CoreUtils::dataSourceParser($response['content'], $response['mixins']),
        'time' => sprintf('%f', $__start + microtime(true)),
    ];
} catch (MyRadioException $e) {
    header('HTTP/1.1 '.$e->getCode().' '.$e->getCodeName());
    header('Content-Type: application/json');
    $data = [
        'status' => 'FAIL',
        'payload' => $e->getMessage(),
        'time' => sprintf('%f', $__start + microtime(true)),
    ];
}

echo json_encode($data);
