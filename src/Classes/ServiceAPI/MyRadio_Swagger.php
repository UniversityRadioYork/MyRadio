<?php

/**
 * Provides the MyRadio_Swagger class for MyRadio
 * @package MyRadio_API
 */

namespace MyRadio\ServiceAPI;

use \ReflectionMethod;
use \ReflectionClass;
use \ReflectionException;

use \MyRadio\Config;
use \MyRadio\Database;
use \MyRadio\MyRadio\CoreUtils;

/**
 * The Swagger class is an Implementation of https://developers.helloreverb.com/swagger/
 * @version 20130731
 * @author Lloyd Wallis <lpw@ury.org.uk>
 * @package MyRadio_API
 * @uses \Database
 *
 * @todo Detect Response Types
 * @todo Parse docblocks to get variable information
 */
class MyRadio_Swagger
{
    /** THIS HALF DEALS WITH RESOURCES LISTING * */
    public static function resources()
    {
        $data = [
            'apiVersion' => 0.1,
            'swaggerVersion' => 1.2,
            'basePath' => Config::$api_url,
            'authorizations' => ['apiKey' => ['type' => 'api_key', 'passAs' => 'query']],
            'apis' => []
        ];

        foreach (self::getApiClasses() as $api => $myury) {
            if ($myury == __CLASS__) {
                continue;
            }
            $class = new ReflectionClass($myury);
            $meta = self::getClassDoc($class);
            $data['apis'][] = ['path' => '/resources/' . $api, 'description' => $meta['short_desc']];
        }

        return $data;
    }

    public static function getApiClasses()
    {
        $data = Database::getInstance()->fetchAll('SELECT class_name, api_name FROM myury.api_class_map ORDER BY api_name');
        $result = [];

        foreach ($data as $row) {
            $result[$row['api_name']] = $row['class_name'];
        }

        return $result;
    }

    /** THIS HALF DEALS WITH API Declarations * */
    private $class;

    public function __construct($class)
    {
        $this->class = $class;
    }

    public function toDataSource()
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
            'swaggerVersion' => 1.2,
            'apiVersion' => 0.2,
            'basePath' => Config::$api_url . '/' . $this->class,
            'apis' => [],
            'models' => []
        ];

        $refClass = new ReflectionClass($this->getApiClasses()[$this->class]);
        $constructor = new ReflectionMethod($this->getApiClasses()[$this->class], '__construct');

        foreach ($refClass->getMethods() as $method) {
            if (!$method->isPublic() or in_array($method->getName(), $blocked_methods)) {
                continue;
            }
            $meta = $this->getMethodDoc($method);
            /**
             * Add the custom @api docblock option
             * @api may be GET, POST...
             */
            $comment = preg_replace('/^.*\@api ([A-Z]+)?.*$/s', '$1', $method->getDocComment(), 1, $count);
            if ($count === 1) {
                $meta['api'] = $comment;
            } else {
                $meta['api'] = (substr($method->getName(), 0, 3) === 'set' or $method->getName() === 'create') ? 'POST' : 'GET';
            }

            //Build the API URL
            $path = '/';
            if (!$method->isStatic() &&
                    !($constructor->isPublic() && $constructor->getParameters() == null)) {
                $path .= '{id}/';
            }

            $params = [];
            if ($method->getName() !== 'toDataSource') {
                $path .= $method->getName() . '/';
            } else {
                //toDataSource has a full option
                $params[] = [
                    "paramType" => "query",
                    "name" => 'full',
                    "description" => "Some objects can optionally return a small or large response. By default, a full response is on, although it is intended for this to change.",
                    "type" => "boolean",
                    "required" => false,
                    "allowMultiple" => false,
                    "defaultValue" => true
                ];
            }

            //Build the parameters list
            //id is a parameter if the method is not static
            //unless the constructor is public and takes no args
            if (!$method->isStatic() &&
                    !($constructor->isPublic() && $constructor->getParameters() == null)) {
                $params[] = [
                    "paramType" => "path",
                    "name" => "id",
                    "description" => "The unique identifier of the $this->class to be acted on. An int for most Objects, but some are Strings.",
                    "type" => "int",
                    "required" => true,
                    "allowMultiple" => false
                ];
            }
            //now do the ones for the specific method
            foreach ($method->getParameters() as $param) {
                $params[] = [
                    "paramType" => "query",
                    "name" => $param->getName(),
                    "description" => (empty($meta['params'][$param->getName()]['description']) ? : $meta['params'][$param->getName()]['description']),
                    "type" => (empty($meta['params'][$param->getName()]['type']) ? 'int' : $meta['params'][$param->getName()]['type']),
                    "required" => !$param->isOptional(),
                    "allowMultiple" => false,
                    "defaultValue" => $param->isDefaultValueAvailable() ? $param->getDefaultValue() : null
                ];
            }

            //cool, now add the method in
            $data['apis'][] = [
                "path" => $path,
                "description" => $meta['short_desc'],
                "operations" => [
                    [
                        "method" => $meta['api'],
                        "nickname" => $method->getName(),
                        "\$ref" => $meta['return_type'],
                        "parameters" => $params,
                        "summary" => $meta['short_desc'],
                        "notes" => $meta['long_desc']
                    ]
                ]
            ];
        }

        return $data;
    }

    private static function getClassDoc(ReflectionClass $class)
    {
        $doc = $class->getDocComment();

        $lines = explode("\n", trim(preg_replace('/(\/\*\*)|(\n\s+\*\/?[^\S\r\n]?)/', "\n", $doc), " \n"));

        //Parse for short description. This is up to the first blank line.
        $i = 0;
        $short_desc = '';
        while (isset($lines[$i]) && !empty($lines[$i]) && substr($lines[$i], 0, 1) !== '@') {
            $short_desc .= $lines[$i] . ' ';
            $i++;
        }

        //Parse for long description. This is until the first @
        $long_desc = '';
        while (isset($lines[$i]) && substr($lines[$i], 0, 1) !== '@') {
            $long_desc .= $lines[$i] . ' ';
            $i++;
        }

        //Now parse for docblock things
        $params = [];
        $return_type = 'Set';
        while (isset($lines[$i])) {
            //Skip ones that are out of place.
            if (substr($lines[$i], 0, 1) !== '@') {
                $i++;
                continue;
            }
            $key = preg_replace('/^\@([a-zA-Z]+)(.*)$/', '$1', $lines[$i]);
            if (empty($key)) {
                continue;
            }
            switch ($key) {
                //Deal with $params
                case 'param':
                    /**
                     * info[0] should be "@param"
                     * info[1] should be data type
                     * info[2] should be parameter name
                     * info[3] should be the description
                     */
                    $info = explode(' ', $lines[$i], 4);
                    $arg = str_replace('$', '', $info[2]); //Strip the $ from variable name
                    $params[$arg] = ['type' => $info[1], 'description' => empty($info[3]) ? : $info[3]];
                    //For any following lines, if they don't start with @, assume it's a continuation of the description
                    $i++;
                    while (isset($lines[$i]) && substr($lines[$i], 0, 1) !== '@') {
                        if (empty($lines[$i])) {
                            $params[$arg]['description'] .= '<br>';
                        }
                        $params[$arg]['description'] .= ' ' . $lines[$i];
                        $i++;
                    }
                    break;
                default:
                    $i++;
                    break;
            }
        }

        return [
            'short_desc' => trim($short_desc),
            'long_desc' => trim($long_desc),
            'params' => $params,
            'return_type' => $return_type
        ];
    }

    public static function parseMethodDoc($doc)
    {
        $raw = explode("\n", trim(preg_replace('/(\/\*\*)|(\n*\s+\*\/?\s?)/', "\n", $doc->getDocComment()), " \n"));

        $lines = [''];
        $keys = [];
        foreach ($raw as $line) {
            if (empty($raw)) {
                $lines[] = '';
            } elseif (substr($line, 0, 1) === '@') {
                $key = preg_replace('/^\@([a-zA-Z]+)(.*)$/', '$1', $line);
                $keys[$key][] = preg_replace('/^\@([a-zA-Z]+) (.*)$/', '$2', $line);
            } else {
                $lines[sizeof($lines)-1] .= $line . ' ';
            }
        }
        return ['lines' => $lines, 'keys' => $keys];
    }

    private function getMethodDoc(ReflectionMethod $method)
    {
        $doc = self::parseMethodDoc($method);

        $short_desc = array_shift($doc['lines']);

        //Parse for long description. This is until the first @
        $long_desc = implode('<br>', $doc['lines']);

        //We append the auth requirements to the long description
        $requirements = MyRadio_APIKey::getCallRequirements(
            $this->getApiClasses()[$this->class],
            $method->getName()
        );
        if ($requirements === null) {
            $long_desc .= '<br>This API Call requires a Full API Access Key.';
        } elseif (empty($requirements)) {
            $long_desc .= '<br>Any API Key can Call this method.';
        } else {
            $long_desc .= '<br>The following permissions enable access to this method:';
            foreach ($requirements as $typeid) {
                $long_desc .= '<br> - ' . CoreUtils::getAuthDescription($typeid);
            }
        }

        //Now parse for docblock things
        $params = [];
        $return_type = 'Set';
        foreach ($doc['keys'] as $key => $values) {
            switch ($key) {
                //Deal with $params
                case 'param':
                    /**
                     * info[0] should be "@param"
                     * info[1] should be data type
                     * info[2] should be parameter name
                     * info[3] should be the description
                     */
                    $info = explode(' ', $values[0], 4);
                    $arg = str_replace('$', '', $info[2]); //Strip the $ from variable name
                    $params[$arg] = ['type' => $info[1], 'description' => empty($info[3]) ? : $info[3]];
                    break;
                default:
                    $i++;
                    break;
            }
        }

        return [
            'short_desc' => trim($short_desc),
            'long_desc' => trim($long_desc),
            'params' => $params,
            'return_type' => $return_type
        ];
    }
}
