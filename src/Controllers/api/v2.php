<?php
use \ReflectionMethod;
use \ReflectionClass;
use \ReflectionException;

use \MyRadio\Config;
use \MyRadio\MyRadioException;
use \MyRadio\MyRadio\CoreUtils;
use \MyRadio\MyRadio\MyRadioSession;
use \MyRadio\ServiceAPI\MyRadio_APIKey;
use \MyRadio\ServiceAPI\MyRadio_Swagger2;
use \MyRadio\ServiceAPI\MyRadio_User;

header('Content-Type: application/json');

//Strip everything from the URL before the version
$url = explode(Config::$api_url . '/v2/', $_SERVER['REQUEST_URI'])[1];

if ($url === 'swagger.json') {
	echo json_encode(MyRadio_Swagger2::resources());
	return;
}

$parts = explode('/', $url);
$op = strtolower($_SERVER['REQUEST_METHOD']);
$class = preg_replace('/[^0-9a-zA-Z-_]+/', '', $parts[0]);
$method = preg_replace('/[^0-9a-zA-Z-_]+/', '', $parts[sizeof($parts)-1]);
$id = null;
if (sizeof($parts) === 3) {
	$id = $parts[1];
}

try {
	$response = MyRadio_Swagger2::handleRequest($op, $class, $method, $id);
	$data = [
	    'status' => 'OK',
	    'payload' => CoreUtils::dataSourceParser($response),
	    'time' => sprintf('%f', $__start + microtime(true))
	];
} catch (MyRadioException $e) {
	header('HTTP/1.1 ' . $e->getCode() . ' ' . $e->getCodeName());
	$data = [
	    'status' => 'FAIL',
	    'payload' => $e->getMessage(),
	    'time' => sprintf('%f', $__start + microtime(true))
	];
}



echo json_encode($data);