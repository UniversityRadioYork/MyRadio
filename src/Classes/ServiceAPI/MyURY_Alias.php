<?php
/**
 * Provides the Alias Abstract class for MyURY
 * @package MyURY_Mail
 */

/**
 * The Alias class is used to define the Interface and common features of all
 * Aliases.
 * 
 * It is not an interface itself so that it can provide the code for some
 * methods. If you wanted, you could make an Interface that this implements.
 * 
 * @version 20130822
 * @author Lloyd Wallis <lpw@ury.org.uk>
 * @package MyURY_Mail
 * @uses \Database
 * 
 */
abstract class MyURY_Alias extends ServiceAPI {

  /**
   * Singleton store.
   * @var mixed
   */
  protected static $aliases = array();
  /**
   * The ID of the Alias
   * @var int
   */
  protected $aliasid;
  /**
   * The name of the alias (i.e. the source prefix)
   * If this is an alias from foo@ury.org.uk to bar@ury.org.uk, this value is
   * 'foo'
   * @var String
   */
  protected $name;
  /**
   * Depends on the Alias type. See subclass descriptions.
   * @var mixed
   */
  protected $target;

  public static function getInstance($id = -1) {
    if (!is_numeric($id)) {
      throw new MyURYException($id.' is not a valid Alias ID!', 400);
    }
    if (!isset(self::$aliases[$id])) {
      self::$aliases[$id] = new (get_called_class())($id);
    }
    return self::$aliases[$id];
  }
  
  /**
   * Returns all the Aliases available.
   */
  abstract public static function getAllAliases();
  
  /**
   * Get the ID fo this Alias
   * @return int
   */
  public function getID() {
    return $this->aliasid;
  }
  
  /**
   * Returns the string prefix of the Alias. Each subclass has its own ID
   * pool. They are not globally unique.
   * 
   * @return String
   */
  public function getName() {
    return $this->name;
  }
  
  /**
   * Returns what the Alias maps to. Tha response depends on the implementation.
   * It may be another string, a User, a List, an Officer or something else.
   * @return mixed
   */
  public function getTarget() {
    return $this->target;
  }
  
  /**
   * Returns data about the Alias for the API.
   * 
   * @param bool $full
   * @return Array
   */
  public function toDataSource($full = true) {
    $data = [
        'aliasid' => $this->getID(),
        'name' => $this->getName(),
        'target' => CoreUtils::dataSourceParser($this->getTarget(), false)
    ];
    
    return $data;
  }

}
