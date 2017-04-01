<?php

use \MyRadio\MyRadioException;
use \MyRadio\MyRadio\CoreUtils;
use \MyRadio\ServiceAPI\MyRadio_Swagger;

/*
 * Get the API key, or complain if there isn't one.
 * Do this after RTFM.
 *
 * If accessing Resources, then we give it an API Key with no permissions
 * in order to access it.
 */
if (isset($_REQUEST['apiKey'])) {
    $_REQUEST['api_key'] = $_REQUEST['apiKey'];
}
if (empty($_REQUEST['api_key']) && $class === 'resources') {
    $_REQUEST['api_key'] = 'IUrnsb8AMkjqDRdfXvOMe3DqHLW8HJ1RNBPNJq3H1FQpiwQDs7Ufoxmsf5x'
        .'ZE9XEbQErRO97DG4xfyVAO7LuS2dOiVNZYoxkk4fEhDt8wR4sLXbghidtM5rLHcgkzO10';
}

$api_key = MyRadio_Swagger::getAPICaller();
if (!$api_key) {
    api_error(401, 'An API Key must be provided.');
}

/*
 * Available API Classes
 */
$classes = MyRadio_Swagger::getApiClasses();
if (!isset($classes[$class])) {
    api_error(404);
}
$classReflection = new ReflectionClass($classes[$class]);

/*
 * Okay, now lets see if the second part of the URL is numeric. If it is, that's definitely an ID
 */
if (is_numeric($params[1])) {
    $id = (int) $params[1];
    $method = empty($params[2]) ? 'toDataSource' : $params[2];
} elseif (!empty($params[2])) {
    /*
     * Now, if it's a String, it could be an ID or method. If there's a 3rd part this is the ID.
     */
    $id = $params[1];
    $method = $params[2];
} else {
    /*
     * If there's two parts, this could be a Static method call *or* the ID of something with
     * a String key. We'll have to ask the class if the method exists.
     */
    if (method_exists($classes[$class], $params[1])) {
        $id = -1;
        $method = $params[1];
    } else {
        $id = $params[1];
        $method = 'toDataSource';
    }
}

/*
 * Woo! Now we know everything we need to get started.
 * Now let's check if the method exists and get its details
 */
try {
    $methodReflection = $classReflection->getMethod($method);
} catch (ReflectionException $e) {
    api_error(404);
}

/*
 * If it's an OPTIONS request report what methods are allowed
 * Otherwise, check they're using one of those methods
 */
$allowed_methods = MyRadio_Swagger::getOptionsAllow($methodReflection);
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    header('Allow: '.implode(', ', $allowed_methods));
    exit;
} elseif (!in_array($_SERVER['REQUEST_METHOD'], $allowed_methods)) {
    header('Allow: '.implode(', ', $allowed_methods));
    api_error(405);
}

/*
 * Okay, the method exists. Does the given API key have access to it?
 */
if (!$api_key->canCall($classes[$class], $method)) {
    api_error(403, 'Your API Key ('.$api_key->getID().') does not have access to this method.');
} else {
    /*
     * Map the paramaters
     */
    $args = [];
    foreach ($methodReflection->getParameters() as $param) {
        if (isset($_REQUEST[$param->getName()])) {
            //If the param has a class hint, initialise the class, assuming the argument is an ID.
            if ($param->getClass() !== null) {
                try {
                    $hint = $param->getClass()->getName();
                    $args[$param->getName()] = $hint::getInstance($_REQUEST[$param->getName()]);
                } catch (MyRadioException $ex) {
                    api_error(
                        400,
                        'Parameter '.$param->getName().' got an invalid ID. Must be an ID for '.$param->getClass().'.'
                    );
                }
            } else {
                $args[$param->getName()] = $_REQUEST[$param->getName()];
            }
        } elseif (!$param->isOptional()) {
            //Uh-oh, required option missing
            api_error(400, 'Parameter '.$param->getName().' is required but not provided.');
        }
    }

    /*
     * From here on out, return a happy error message. If something goes awry.
     */
    try {
        /*
         * Okay, now if the method isn't static, then we need to initialise an object.
         */
        if (!$methodReflection->isStatic()) {
            if (method_exists($classes[$class], 'getInstance')) {
                $object = $classes[$class]::getInstance($id);
            } else {
                $object = new $classes[$class]($id);
            }
        } else {
            $object = null;
        }

        /*
         * Let's process the request!
         */
        $result = invokeArgsNamed($methodReflection, $object, $args);
    } catch (MyRadioException $e) {
        api_error($e->getCode(), $e->getMessage());
    }

    header('Content-Type: application/json');

    $data = $class === 'resources' ? $result : [
        'status' => 'OK',
        'payload' => CoreUtils::dataSourceParser($result, $_REQUEST['mixins'] ?: []),
        'time' => sprintf('%f', $__start + microtime(true)),
    ];

    echo json_encode($data);
}
