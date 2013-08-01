<?php
/**
 * Provides the MyURY_Swagger class for MyURY
 * @package MyURY_Core
 */

/**
 * The Swagger class is an Implementation of https://developers.helloreverb.com/swagger/
 * @version 20130731
 * @author Lloyd Wallis <lpw@ury.org.uk>
 * @package MyURY_API
 * @uses \Database
 * 
 * @todo Detect Response Types
 * @todo Parse docblocks to get variable information
 */

class MyURY_Swagger {

  /** THIS HALF DEALS WITH RESOURCES LISTING **/
  public static function resources() {
    $data = [
      'apiVersion' => 0.1,
      'swaggerVersion' => 1.2,
      'basePath' => Config::$api_url,
      'apis' => []
    ];
    
    foreach (self::getApiClasses() as $api => $myury) {
      if ($myury == __CLASS__) continue;
      $class = new ReflectionClass($myury);
      $data['apis'][] = ['path' => '/resources/'.$api, 'description' => $class->getDocComment()];
    }
    
    return $data;
    
  }
  
  public static function getApiClasses() {
    $data = Database::getInstance()->fetch_all('SELECT class_name, api_name FROM myury.api_class_map ORDER BY api_name');
    $result = [];
    
    foreach ($data as $row) {
      $result[$row['api_name']] = $row['class_name'];
    }
    
    return $result;
  }
  
  public static function getInstance($class) {
    return new self($class);
  }
  
  /** THIS HALF DEALS WITH API Declarations **/
  private $class;
  public function __construct($class) {
    $this->class = $class;
  }
  
  public function toDataSource() {
    $blocked_methods = ['getInstance', 'wakeup', '__wakeup', 'removeInstance', '__toString', 'setToDataSource'];
    $data = [
      'apiVersion' => 0.1,
      'swaggerVersion' => 1.2,
      'basePath' => Config::$api_url.'/'.$this->class,
      'apis' => [],
      'models' => []
    ];
    
    $ref = new ReflectionClass($this->getApiClasses()[$this->class]);
    
    foreach ($ref->getMethods() as $method) {
      if (!$method->isPublic() or in_array($method->getName(), $blocked_methods)) continue;
      $meta = $this->getMethodDoc($method);
      
      //Build the API URL
      $path = '/';
      if (!$method->isStatic()) $path .= '{id}/';
      if ($method->getName() !== 'toDataSource') $path .= $method->getName().'/';
      
      //Build the paramaters list
      $params = [];
      //id is a parameter if the method is not static
      if (!$method->isStatic()) {
        $params[] = [
          "paramType"=> "path",
          "name"=> "id",
          "description"=> "The unique identifier of the $this->class to be acted on. A string for most Objects, but some are Strings.",
          "dataType"=> "int",
          "required"=> true,
          "allowMultiple"=> false
        ];
      }
      //now do the ones for the specific method
      foreach ($method->getParameters() as $param) {
        $params[] = [
            "paramType"=> "query",
            "name"=> $param->getName(),
            "description"=> (empty($meta['params'][$param->getName()]['description']) ?: $meta['params'][$param->getName()]['description']),
            "dataType" => (empty($meta['params'][$param->getName()]['type']) ? 'int' : $meta['params'][$param->getName()]['type']),
            "required" => !$param->isOptional(),
            "allowMultiple" => false,
            "defaultValue" => $param->isDefaultValueAvailable() ? $param->getDefaultValue() : null
        ];
      }
      
      //cool, now add the method in
      $data['apis'][] = [
          "path"=> $path,
          "description"=> $method->getDocComment(),
          "operations"=> [
              [
                  "httpMethod"=> "GET",
                  "nickname"=> $method->getName(),
                  "responseClass"=> $meta['return_type'],
                  "parameters"=> $params,
                  "summary"=> $meta['short_desc'],
                  "notes" => $meta['long_desc']
              ]
          ]
      ];
      
    }
    
    return $data;
  }
  
  private function getMethodDoc(ReflectionMethod $method) {
    $doc = $method->getDocComment();
    
    $lines = explode("\n", trim(preg_replace('/(\/\*\*)|(\n\s+\*\/?\s?)/', "\n", $doc), " \n"));
    
    //Parse for short description. This is up to the first blank line.
    $i = 0;
    $short_desc = '';
    while (isset($lines[$i]) && !empty($lines[$i]) && substr($lines[$i],0,1) !== '@') {
      $short_desc .= $lines[$i].' ';
      $i++;
    }
    
    //Parse for long description. This is until the first @
    $long_desc = '';
    while (isset($lines[$i]) && substr($lines[$i],0,1) !== '@') {
      $long_desc .= $lines[$i].' ';
      $i++;
    }
    
    //Now parse for docblock things
    $params = [];
    $return_type = 'Set';
    while (isset($lines[$i])) {
      //Skip ones that are out of place.
      if (substr($lines[$i],0,1) !== '@') {
        $i++;
        continue;
      }
      $key = preg_replace('/^\@([a-zA-Z]+)(.*)$/', '$1', $lines[$i]);
      if (empty($key)) continue;
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
          $arg = str_replace('$','',$info[2]); //Strip the $ from variable name
          $params[$arg] = ['type' => $info[1], 'description' => empty($info[3]) ?: $info[3]];
          //For any following lines, if they don't start with @, assume it's a continuation of the description
          $i++;
          while (isset($lines[$i]) && substr($lines[$i],0,1) !== '@') {
            if (empty($lines[$i])) $params[$arg]['description'] .= '<br>';
            $params[$arg]['description'] .= ' '.$lines[$i];
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

}