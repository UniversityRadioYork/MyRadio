<?php

/**
 * Provides the MyRadio_Swagger2 class for MyRadio
 * @package MyRadio_API
 */

namespace MyRadio\ServiceAPI;

use \ReflectionMethod;
use \ReflectionClass;
use \ReflectionException;

use \MyRadio\Config;
use \MyRadio\Database;
use \MyRadio\MyRadioException;
use \MyRadio\MyRadio\CoreUtils;

/**
 * The Swagger class is an Implementation of https://developers.helloreverb.com/swagger/
 *
 * @package MyRadio_API
 * @uses    \Database
 *
 * @todo Detect Response Types
 * @todo Parse docblocks to get variable information
 */
class MyRadio_Swagger2 extends MyRadio_Swagger
{
    /**
     * Returns if the given Authenticator can call the given Class/Method/Mixin combination
     * @param \MyRadio\IFace\APICaller  $auth   The Authenticator to validate the request against
     * @param String                    $class  The internal name of the class to validate against
     * @param String                    $method The internal name of the method to validate against
     * @param String[]                  $mixins For toDataSource requests, zero or more mixins to validate against
     * @return boolean
     */
    private static function validateRequest($auth, $class, $method, $mixins) {
        return $auth->canCall($class, $method) &&
            ($method !== 'toDataSource' || $auth->canMixin($class, $mixins));
    }

    private static function getArgs($op) {
        $args = [];

        switch ($op) {
            case 'get':
                $args = $_GET;
                break;
            case 'post':
                $args = $_POST;
                break;
            case 'put':
                //ya rly
                parse_str(file_get_contents("php://input"), $args);
                break;
        }

        // Check mixins too
        if (isset($args['mixins'])) {
            $args = ['mixins' => array_filter(explode(',', $args['mixin']))];
        }

        return $args;
    }

    /**
     * Process an /api/v2 request
     * @param string $op The HTTP request method (GET/PUT/POST/DELETE...)
     * @param string $class The URI-name of the class being acted on
     * @param string $method The URI-name of the method being acted on
     * @param mixed  $id The ID of the item being acted on, if the method is non-static
     */
    public static function handleRequest($op, $class, $method, $id = null)
    {
        $classes = array_flip(self::getApis());

        if (!isset($classes[$class])) {
            throw new MyRadioException("$class endpoint does not exist.", 404);
        }

        $refClass = new MyRadio_Swagger2($classes[$class]);
        $paths = $refClass->getClassInfo()['children'];

        $path = '';
        if ($id) {
            $path = $path . '/{id}';
        }
        if ($method) {
            $path = $path . '/' . $method;
        }

        if (!isset($paths[$path])) {
            throw new MyRadioException("$class has no child $method.", 404);
        }

        if (!isset($paths[$path][$op])) {
            throw new MyRadioException("$path does not have a valid $op handler.", 405);
        }

        $args = self::getArgs($op);

        if ($id) {
            if (method_exists($classes[$class], 'getInstance')) {
                $object = $classes[$class]::getInstance($id);
            } else {
                $object = new $classes[$class]($id);
            }
        } else {
            $object = null;
        }

        //Cool, it's valid. Can they get at it?
        $caller = self::getAPICaller();

        if (!$caller) {
            throw new MyRadioException('No valid authentication data provided.', 401);
        } elseif (self::validateRequest($caller, $classes[$class], $paths[$path][$op]->getName(), $args['mixins'])) {
            $caller->logCall($_SERVER['REQUEST_URI'], $op === 'get' ? $args : [$op]);
            return invokeArgsNamed($paths[$path][$op], $object, $args);
        } else {
            throw new MyRadioException('Caller cannot access this method.', 403);
        }
    }

    /**
     * THIS HALF DEALS WITH RESOURCES LISTING
     */
    public static function resources()
    {
        $apis = self::getApis();
        $data = [
            'swagger' => '2.0',
            'basePath' => Config::$api_uri . 'v2',
            'host' => $_SERVER['HTTP_HOST'],
            'info' => [
                'title' => 'MyRadio API',
                'description' => 'The MyRadio API provides vaguely RESTful access to many of the internal workings of your friendly local radio station.',
                'termsOfService' => 'The use of this API is permitted only for applications which have been issued an API key, and only then within the additional Terms of Service issued with that application\'s key. Any other use is strictly prohibited. The MyRadio API may be used for good, but not evil. The lighter 15 of the 50 grey areas are also permitted for all authorised applications.',
                'version' => '2.0'
            ],
            'schemes' => ['https'],
            'consumes' => [],
            'produces' => ['application/json'],
            'paths' => self::getPaths($apis),
            'tags' => self::getTags($apis),
            'parameters' => [
                'idParam' => [
                    'name' => 'id',
                    'in' => 'path',
                    'description' => 'The ID of the item to work with.',
                    'required' => true,
                    'type' => 'integer'
                ],
                'dataSourceFull' => [
                    'name' => 'full',
                    'in' => 'query',
                    'description' => 'Deprecated. Used to return more details in object GETs.',
                    'required' => false,
                    'type' => 'boolean',
                    'default' => false
                ]
            ],
            'responses' => [
                'invalidInput' => [
                    'description' => 'Invalid input for this operation.'
                ]
            ]
        ];

        return $data;
    }

    private static function getApis()
    {
        return json_decode(file_get_contents(__DIR__ . '/../../../schema/api.json'), true);
    }

    private static function getParameters($method, $doc)
    {
        $parameters = [];

        if (!$method->isStatic()) {
            $parameters[] = [
                '$ref' => '#/parameters/idParam'
            ];
        }

        if ($method->name === 'toDataSource' && $method->getNumberOfParameters() === 1) {
            if (!empty($doc['mixins'])) {
                $description = 'A list of mixins to provide additional information in the response. Possible values:';
                foreach ($doc['mixins'] as $mixin => $desc) {
                    $description .= "<br>$mixin: $desc";
                }
                $parameters[] = [
                    'name' => 'mixins',
                    'in' => 'query',
                    'description' => $description,
                    'required' => false,
                    'type' => 'array',
                    'items' => [
                        'type' => 'string',
                        'format' => 'string'
                    ],
                    'collectionFormat' => 'csv',
                    'default' => []
                ];
            } else {
                $parameters[] = [
                    '$ref' => '#/parameters/dataSourceFull'
                ];
            }
        }

        return $parameters;
    }

    private static function getPaths($apis)
    {
        $cache_class = Config::$cache_provider;
        $cache = $cache_class::getInstance();

        $paths = $cache->get('api_pathmap_v2');
        if (!$paths) {
             $paths = [];

            foreach ($apis as $class => $public_name) {
                $api = new MyRadio_Swagger2($class);

                foreach ($api->getClassInfo()['children'] as $method_name => $child) {

                    foreach ($child as $op => $reflector) {
                        $data = self::getMethodDoc($reflector);

                        $paths['/' . $public_name . $method_name][$op] = [
                            'summary' => $data['short_desc'],
                            'description' => $data['long_desc'],
                            'tags' => [$public_name],
                            'operationId' => $class . ':' . $reflector->getName(),
                            'parameters' => self::getParameters($reflector, $data),
                            'responses' => [
                                '400' => ['$ref' => '#/responses/invalidInput']
                            ],
                            'deprecated' => $data['deprecated'],
                            'security' => []
                        ];
                    }
                }
            }

            $cache->set('api_pathmap_v2', $paths, 600);
        }

        return $paths;
    }

    private static function getTags($apis)
    {
        $tags = [];

        foreach ($apis as $class => $public_name) {
            $api = new MyRadio_Swagger2($class);

            $tag = [
                "name" => $public_name,
                "description" => $api->getClassInfo()['description']
            ];

            $tags[] = $tag;
        }

        return $tags;
    }

    public function getClassInfo()
    {
        $blocked_methods = [
            'getInstance',
            'wakeup',
            '__wakeup',
            'removeInstance',
            '__toString',
            'setToDataSource',
            '__construct'
        ];

        $data = [
            'description' => '',
            'children' => []
        ];

        $refClass = new ReflectionClass($this->class);
        $constructor = new ReflectionMethod($this->class, '__construct');

        $data['description'] = self::getClassDoc($refClass)['short_desc'];

        foreach ($refClass->getMethods() as $method) {
            if (
                (!$method->isPublic())
                || in_array($method->getName(), $blocked_methods)
                || substr($method->getName(), strlen($method->getName()) - 4) === 'Form'
                ) {
                continue;
            }

            $name = $method->getName();

            if ($name === 'toDataSource') {
                $op = 'get';
                $public_name = '';
            } elseif (CoreUtils::startsWith($name, 'set')) {
                $op = 'put';
                $public_name = '/' . strtolower(substr($name, 3));
            } elseif (CoreUtils::startsWith($name, 'get')) {
                $op = 'get';
                $public_name = '/' . strtolower(substr($name, 3));
            } elseif ($name === 'create') {
                $op = 'post';
                $public_name = '';
            } else {
                $op = 'put';
                $public_name = '/' . strtolower($name);
            }

            if (!$method->isStatic()) {
                $public_name = '/{id}' . $public_name;
            }

            $data['children'][$public_name][$op] = $method;
        }

        return $data;
    }
}
