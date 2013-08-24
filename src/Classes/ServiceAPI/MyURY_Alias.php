<?php
/**
 * Provides the Alias Abstract class for MyURY
 * @package MyURY_Mail
 */

/**
 * The Alias class is used to do stuff with Aliases in URY's mail system.
 * 
 * @version 20130824
 * @author Lloyd Wallis <lpw@ury.org.uk>
 * @package MyURY_Mail
 * @uses \Database
 * 
 */
class MyURY_Alias extends ServiceAPI {

  /**
   * Singleton store.
   * @var mixed
   */
  private static $aliases = array();
  /**
   * The ID of the Alias
   * @var int
   */
  private $alias_id;
  /**
   * The source of the alias
   * If this is an alias from foo@ury.org.uk to bar@ury.org.uk, this value is
   * 'foo'
   * @var String
   */
  private $source;
  /**
   * An array of Lists, Users, Officers and text destinations for the Alias.
   * 
   * Format:<br>
   * {{type: 'text', value: 'dave.tracz'}, ...}
   * 
   * @var mixed[]
   */
  private $destinations;

  
  private function __construct($id) {
    $result = self::$db->fetch_one('SELECT alias_id,'
            . 'source,'
            . '(SELECT array(destination) FROM mail.alias_list '
            . 'UNION SELECT array(destination) FROM mail.alias_member '
            . 'UNION SELECT array(destination) FROM mail.alias_officer '
            . 'UNION SELECT array(destination) FROM mail.alias_text) '
            . 'FROM mail.alias WHERE alias_id=$1',
            [$id]);
    if (empty($result)) {
      throw new MyURYException('Alias '.$id.' does not exist!', 404);
    } else {
      print_r($result);
      $this->alias_id = (int)$id;
      $this->source = $result['source'];
    }
  }
  
  /**
   * 
   * @param type $id
   * @return type
   * @throws MyURYException
   */
  public static function getInstance($id = -1) {
    if (!is_numeric($id)) {
      throw new MyURYException($id.' is not a valid Alias ID!', 400);
    }
    if (!isset(self::$aliases[$id])) {
      self::$aliases[$id] = new self($id);
    }
    return self::$aliases[$id];
  }
  
  /**
   * Returns all the Aliases available.
   * @return array
   */
  public static function getAllAliases() {
    return self::setToDataSource(self::$db->fetch_column(
            'SELECT alias_id FROM mail.alias'));
  }
  
  /**
   * Get the ID fo this Alias
   * @return int
   */
  public function getID() {
    return $this->alias_id;
  }
  
  /**
   * Returns the string prefix of the Alias.
   * 
   * @return String
   */
  public function getSource() {
    return $this->source;
  }
  
  /**
   * Returns what the Alias maps to.
   * 
   * Format:<br>
   * {{type: 'text', value: 'dave.tracz'}, ...}
   * 
   * @return mixed[]
   */
  public function getDestinations() {
    return $this->destinations;
  }
  
  /**
   * Returns data about the Alias for the API.
   * 
   * @param bool $full
   * @return Array
   */
  public function toDataSource($full = true) {
    $data = [
        'alias_id' => $this->getID(),
        'source' => $this->getName(),
        'destinations' => CoreUtils::dataSourceParser($this->getDestinations(), false)
    ];
    
    return $data;
  }

}
