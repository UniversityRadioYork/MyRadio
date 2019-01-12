<?php

use \MyRadio\Config;

$__start = -microtime(true);
/*
 * This MyRadio Extension exposes some of MyRadio's internal classes as a REST API.
 * It aims to be compatible with https://developers.helloreverb.com/swagger/
 *
 * @todo Management interfaces to configure keys and expose methods
 */
// Configure MyRadio & Set API Settings
define('SILENT_EXCEPTIONS', false);
define('DISABLE_SESSION', true);
define('JSON_DEBUG', true);

require_once __DIR__.'/../Controllers/root_cli.php';

ob_start();
ini_set('display_errors', 'On');
error_reporting(E_ALL);

/**
 * Handle API errors.
 */
function api_error($code, $message = null)
{
    ob_end_clean();
    $messages = [
        400 => 'Bad Request',
        401 => 'Unauthorized',
        403 => 'Forbidden',
        404 => 'File Not Found',
        405 => 'Method Not Allowed',
        500 => 'Internal Server Error',
    ];
    header("HTTP/1.1 $code {$messages[$code]}");
    header('Content-Type: application/json');
    echo json_encode(
        [
            'status' => $code,
            'time' => sprintf('%f', $GLOBALS['__start'] + microtime(true)),
            'message' => $message,
        ]
    );
    //Log an API failure so it appears in the status graphs.
    trigger_error('API Error: ['.$code.'] '.$message."\nSource: ".$_SERVER['REMOTE_ADDR']);
    exit;
}

/**
 * Names the parameters to make sure they're called in the "correct" order.
 * Adapted from http://stackoverflow.com/q/8649536/995325.
 */
function invokeArgsNamed(ReflectionMethod $refmethod, $object, array $args = [])
{
    $parameters = $refmethod->getParameters();
    if (sizeof($parameters) === 1 and sizeof($args) === 1) {
        // Handle the case where the request body is the param, and also a shortcut...
        $parameters = $args;
    } else {
        foreach ($parameters as &$param) {
            $name = $param->getName();
            if (!$param->isOptional() && !isset($args[$name])) {
                api_error(400, $name . ' is required.');
            }
            $param = isset($args[$name]) ? $args[$name] : $param->getDefaultValue();
        }
    }
    unset($param);

    return $refmethod->invokeArgs($object, $parameters);
}

/*
 * Break up the URL. URLs are of the form /api/ExposedClassName[/id][/method]
 */
//Remove double-slashes that occassionally appear.
$cleaned = str_replace('//', '/', $_SERVER['REQUEST_URI']);
$params = explode('/', str_ireplace(Config::$api_uri, '', explode('?', $cleaned)[0]));
$class = $params[0];

if (empty($class)) {
    //Someone's gone to the home page. Let's tell them to RTFM
    header('Location: rtfm');
    exit;
}

//Go to the right version controller
if (strpos($_SERVER['REQUEST_URI'], Config::$api_uri.'v2/') !== false) {
    require_once '../Controllers/api/v2.php';
} else {
    require_once '../Controllers/api/v1.php';
}
