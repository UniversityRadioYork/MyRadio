<?php

/**
 * Provides the MyRadio_Swagger2 class for MyRadio.
 */
namespace MyRadio\ServiceAPI;

use ReflectionClass;
use MyRadio\Config;
use MyRadio\MyRadioException;
use MyRadio\MyRadio\CoreUtils;
use MyRadio\MyRadio\URLUtils;

/**
 * The Swagger class is an Implementation of https://developers.helloreverb.com/swagger/.
 *
 * @uses    \Database
 *
 * @todo Detect Response Types
 * @todo Parse docblocks to get variable information
 */
class MyRadio_Swagger2 extends MyRadio_Swagger
{

    private static $api_config;

    /**
     * Returns if the given Authenticator can call the given Class/Method/Mixin combination.
     *
     * @param \MyRadio\IFace\APICaller $auth   The Authenticator to validate the request against
     * @param string                   $class  The internal name of the class to validate against
     * @param string                   $method The internal name of the method to validate against
     * @param string[]                 $mixins For toDataSource requests, zero or more mixins to validate against
     *
     * @return bool
     */
    private static function validateRequest($auth, $class, $method, $mixins)
    {
        return $auth->canCall($class, $method) &&
            ($method !== 'toDataSource' || $auth->canMixin($class, $mixins));
    }

    private static function getArgs($op, $method, $arg0)
    {
        $args = [];

        switch ($op) {
            case 'get':
                $args = $_GET;
                break;
            case 'post':
                if (substr_count($_SERVER['CONTENT_TYPE'], 'application/json')) {
                    $args = json_decode(file_get_contents('php://input'), true);
                    if ($method->getNumberOfParameters() === 1) {
                        //Support the case where the entire body is the parameter
                        // This is the more likely case, but I think the other scenario is used somewhere...
                        $args = [$args];
                    }
                } else {
                    $args = $_POST;
                }
                break;
            case 'put':
                if (substr_count($_SERVER['CONTENT_TYPE'], 'application/json')) {
                    $args = json_decode(file_get_contents('php://input'), true);
                } else {
                    parse_str(file_get_contents('php://input'), $args);
                }
                break;
        }

        // Check mixins too
        if (isset($args['mixins'])) {
            $args['mixins'] = array_filter(explode(',', $args['mixins']));
        }

        $parameters = $method->getParameters();

        if (self::isOptionInPathForMethod($method)) {
            $args[$parameters[0]->getName()] = $arg0;
        }

        return $args;
    }

    /**
     * Identify if this method should put its option in its path
     * i.e. as /class/method/option
     *
     * @param ReflectionMethod The method
     * @return bool
     */
    private static function isOptionInPathForMethod($method)
    {
        $doc = self::getMethodDoc($method);
        return self::getMethodOpType($method) === 'get' &&
            $method->getNumberOfRequiredParameters() === 1 &&
            self::getParamType($method->getParameters()[0], $doc) !== 'array';
    }

    /**
     * Identify if the class/method combination given is valid. Useful for routing when paths are ambiguous.
     *
     * @param string $class The URI-name of the class to check
     * @param string $method The URI-name of the method to check
     * @return boolean
     */
    public static function isValidClassMethodCombination($class, $method)
    {
        $classes = array_flip(self::getApis());
        if (!isset($classes[$class])) {
            return false;
        }

        $refClass = new self($classes[$class]);
        return in_array($method, $refClass->getClassMethodsPublicNames());
    }

    /**
     * Process an /api/v2 request.
     *
     * @param string $op     The HTTP request method (GET/PUT/POST/DELETE...)
     * @param string $class  The URI-name of the class being acted on
     * @param string $method The URI-name of the method being acted on
     * @param mixed  $id     The ID of the item being acted on, if the method is non-static
     * @param mixed  $arg0   The value of the parameter after the method name, if there is one
     */
    public static function handleRequest($op, $class, $method, $id = null, $arg0 = null)
    {
        $classes = array_flip(self::getApis());

        if (!isset($classes[$class])) {
            throw new MyRadioException("$class endpoint does not exist.", 404);
        }

        $refClass = new self($classes[$class]);
        $paths = $refClass->getClassInfo()['children'];

        // @todo: This could probably be refactored to be friendlier now isValidClassMethodCombination exists.
        $path = '';
        if ($id) {
            $path = $path.'/{id}';
        }
        // empty string is here so the trailing slash is added back
        if ($method || $method === '') {
            $path = $path.'/'.$method;
        }

        if ($arg0) {
            // array_filter($paths, func, ARRAY_FILTER_USE_KEY) is not running func for me...
            $options = [];
            foreach (array_keys($paths) as $key) {
                if (strpos($key, $path.'/{') === 0) {
                    $options[] = $key;
                    break;
                }
            }

            if (sizeof($options) > 1) {
                throw new MyRadioException('Ambiguous path.', 404);
            }

            $path = $options[0];
        }

        if (!isset($paths[$path])) {
            throw new MyRadioException("$class has no child $method.", 404);
        }

        $options = strtoupper(implode(', ', array_keys($paths[$path]))).', OPTIONS';
        if ($op === 'options') {
            header('Access-Control-Allow-Methods: '.$options); // This is for CORS in browser
            URLUtils::nocontent();
        } elseif (!isset($paths[$path][$op])) {
            header('Allow: '.$options); // This is reference for HTTP 405
            throw new MyRadioException("$path does not have a valid $op handler.", 405);
        }

        $args = self::getArgs($op, $paths[$path][$op], $arg0);

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
            $status = '200 OK';
            if ($paths[$path][$op]->getName() === 'create') {
                $status = '201 Created';
            }

            // Don't send the API key to the function
            unset($args['api_key']);

            $data = ['status' => $status, 'content' => invokeArgsNamed($paths[$path][$op], $object, $args), 'mixins' => $args['mixins']];

            // If this returns a datasourceable array of objects, validate any mixins
            $sample_obj = null;
            if (is_array($data) && sizeof($data) > 0 && is_subclass_of($data[0], 'MyRadio::ServiceAPI::ServiceAPI')) {
                $sample_obj = $data[0];
            } elseif (is_subclass_of($data, 'MyRadio::ServiceAPI::ServiceAPI')) {
                $sample_obj = $data;
            }

            if ($sample_obj && !$caller->canMixin(get_class($sample_obj), $args['mixins'])) {
                throw new MyRadioException('Caller cannot access this method.', 403);
            }

            return $data;
        } else {
            throw new MyRadioException('Caller cannot access this method.', 403);
        }
    }

    /**
     * THIS HALF DEALS WITH RESOURCES LISTING.
     */
    public static function resources()
    {
        $apis = self::getApis();
        $data = [
            'swagger' => '2.0',
            'basePath' => Config::$api_uri.'v2',
            'host' => $_SERVER['HTTP_HOST'],
            'info' => [
                'title' => 'MyRadio API',
                'description' => 'The MyRadio API provides vaguely RESTful access to many of the internal workings '
                                 . 'of your friendly local radio station.',
                'termsOfService' => 'The use of this API is permitted only for applications which have been issued an '
                                  . 'API key, and only then within the additional Terms of Service issued with that '
                                  . 'application\'s key. Any other use is strictly prohibited. The MyRadio API may be '
                                  . 'used for good, but not evil. The lighter 15 of the 50 grey areas are also '
                                  . 'permitted for all authorised applications.',
                'version' => '2.0',
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
                    'type' => 'integer',
                ],
                'dataSourceFull' => [
                    'name' => 'full',
                    'in' => 'query',
                    'description' => 'Deprecated. Used to return more details in object GETs.',
                    'required' => false,
                    'type' => 'boolean',
                    'default' => false,
                ],
            ],
            'responses' => [
                'invalidInput' => [
                    'description' => 'Invalid input for this operation.',
                ],
            ],
            'definitions' => self::getApiConfig()['specs']
        ];

        return $data;
    }

    private static function getApis()
    {
        return self::getApiConfig()["classes"];
    }

    private static function getApiConfig()
    {
        if (!self::$api_config) {
            self::$api_config = json_decode(file_get_contents(__DIR__.'/../../../schema/api.json'), true);
        }
        return self::$api_config;
    }

    private static function getParameters($method, $doc, $op, $public_name)
    {
        $parameters = [];

        if (!$method->isStatic()) {
            $parameters[] = [
                '$ref' => '#/parameters/idParam',
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
                        'format' => 'string',
                    ],
                    'collectionFormat' => 'csv',
                    'default' => [],
                ];
            } else {
                $parameters[] = [
                    '$ref' => '#/parameters/dataSourceFull',
                ];
            }
        } else if (
            $method->name === 'create' &&
            $method->getNumberOfParameters() === 1
            && $op === 'post'
            && self::getApiConfig()['specs'][$public_name]
            ) {
            //This endpoint can have JSON POSTed at it
            $parameters[] = [
                'name' => $public_name,
                'in' => 'body',
                'required' => true,
                'schema' => [
                    '$ref' => '#/definitions/' . $public_name
                ]
            ];
        } else {
            $startIdx = 0;
            $paramReflectors = $method->getParameters();

            if (self::isOptionInPathForMethod($method)) {
                //If only one GET is required, make it URL
                $param = $method->getParameters()[0];
                $parameters[] = [
                    'name' => $param->getName(),
                    'in' => 'path',
                    'description' => self::getParamDescription($param, $doc),
                    'required' => true,
                    'type' => self::getParamType($param, $doc),
                ];
                ++$startIdx;
            }

            for ($i = $startIdx; $i < sizeof($paramReflectors); ++$i) {
                $param = $paramReflectors[$i];
                $definition = [
                    'name' => $param->getName(),
                    'in' => $op === 'get' ? 'query' : 'form',
                    'description' => self::getParamDescription($param, $doc),
                    'required' => !$param->isOptional(),
                    'type' => self::getParamType($param, $doc)
                ];
                // SwaggerUI converts a null to the string "null", which is confusing.
                if ($param->isDefaultValueAvailable() && $param->getDefaultValue() !== null) {
                    $definition['default'] = $param->getDefaultValue();
                }
                $parameters[] = $definition;
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
                $api = new self($class);

                foreach ($api->getClassInfo()['children'] as $method_name => $child) {
                    foreach ($child as $op => $reflector) {
                        $data = self::getMethodDoc($reflector);

                        // Skip methods set to be skipped
                        if ($data['ignore']) {
                            continue;
                        }

                        $paths['/'.$public_name.$method_name][$op] = [
                            'summary' => $data['short_desc'],
                            'description' => $data['long_desc'],
                            'tags' => [$public_name],
                            'operationId' => $class.':'.$reflector->getName(),
                            'parameters' => self::getParameters($reflector, $data, $op, $public_name),
                            'responses' => [
                                '400' => ['$ref' => '#/responses/invalidInput'],
                            ],
                            'deprecated' => $data['deprecated'],
                            'security' => [],
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
            $api = new self($class);

            $tag = [
                'name' => $public_name,
                'description' => $api->getClassInfo()['description'],
            ];

            $tags[] = $tag;
        }

        return $tags;
    }

    private static function getMethodOpType($method)
    {
        $name = $method->getName();

        //Note the ordering is important - create is static!
        if ($name === 'testCredentials'
            || CoreUtils::startsWith($name, 'create')
            || CoreUtils::startsWith($name, 'add')
        ) {
            return 'post';
        }

        if ($name === 'toDataSource'
            || CoreUtils::startsWith($name, 'get')
            || CoreUtils::startsWith($name, 'is')
            || $method->isStatic()
        ) {
            return 'get';
        }

        return 'put';
    }

    private static function getMethodPublicName($method)
    {
        $name = $method->getName();

        if ($name === 'toDataSource' || $name === 'create') {
            return '';
        }

        if (CoreUtils::startsWith($name, 'set') || CoreUtils::startsWith($name, 'get')) {
            return strtolower(substr($name, 3));
        }

        return strtolower($name);
    }

    /**
     * Returns an array of ReflectionMethod objects for each method this class should have exposed.
     * @return ReflectionMethod[]
     */
    private function getReflectedMethods()
    {
        $methods = [];

        $blocked_methods = [
            'getInstance',
            'wakeup',
            '__wakeup',
            'removeInstance',
            '__toString',
            'setToDataSource',
            '__construct',
            'resultSetToObjArray',
            '__destruct'
        ];

        $refClass = new ReflectionClass($this->class);

        foreach ($refClass->getMethods() as $method) {
            if ((!$method->isPublic())
                || in_array($method->getName(), $blocked_methods)
                || substr($method->getName(), strlen($method->getName()) - 4) === 'Form'
                ) {
                continue;
            }

            $methods[] = $method;
        }

        return $methods;
    }

    /**
     * Gets a list of all the public method names for this class.
     * @return String[]
     */
    public function getClassMethodsPublicNames()
    {
        $names = [];
        foreach ($this->getReflectedMethods() as $method) {
            $names[] = self::getMethodPublicName($method);
        }

        return $names;
    }

    public function getClassInfo()
    {
        $data = [
            'description' => '',
            'children' => [],
        ];

        $refClass = new ReflectionClass($this->class);
        $data['description'] = self::getClassDoc($refClass)['short_desc'];

        foreach ($this->getReflectedMethods() as $method) {
            $op = self::getMethodOpType($method);
            $public_name = '/' . self::getMethodPublicName($method);

            if (!$method->isStatic()) {
                $public_name = '/{id}'.$public_name;
            }

            if (self::isOptionInPathForMethod($method)) {
                $public_name .= '{'.$method->getParameters()[0]->getName().'}/';
            }

            $data['children'][$public_name][$op] = $method;
        }

        return $data;
    }
}
