<?php
/**
 * Provides the Officer class for MyURY
 * @package MyURY_Core
 */

/**
 * The Officer class provides information about Committee Officerships.
 * 
 * @version 20130824
 * @author Lloyd Wallis <lpw@ury.org.uk>
 * @package MyURY_Core
 * @uses \Database
 * 
 */
class MyURY_Officer extends ServiceAPI {

  /**
   * Singleton store.
   * @var MyURY_Officer[]
   */
  private static $objs = array();
  /**
   * The ID of the Officer
   * @var int
   */
  private $officerid;
  
  /**
   * Officer title e.g. "Station Manager"
   * @var String
   */
  private $name;
  /**
   * Officer email alias e.g. "station.manager"
   * @var String
   */
  private $alias;
  /**
   * Team the Officership is a member of.
   * @todo
   * @var int|MyURY_Team
   */
  private $team;
  /**
   * The weight of the Officer position, when listing on a page.
   * @var int
   */
  private $ordering;
  /**
   * A description of the position.
   * @var String
   */
  private $description;
  /**
   * (c)urrent or (h)istorical.
   * @var char
   */
  private $status;
  /**
   * (o)fficer, (a)ssistant head of team, (h)ead of team
   * or (m)ember (not actually an Officer, just in team)
   * 
   * @var char
   */
  private $type;

  
  private function __construct($id) {
    $result = self::$db->fetch_one('SELECT * FROM public.officer '
            . 'WHERE officerid=$1', [$id]);
    
    if (empty($result)) {
      throw new MyURYException('Officer '.$id.' does not exist!', 404);
    } else {
      $this->officerid = (int)$id;
      $this->name = $result['officer_name'];
      $this->alias = $result['officer_alias'];
      $this->team = (int)$result['team'];
      $this->ordering = (int)$result['ordering'];
      $this->description = $result['description'];
      $this->status = $result['status'];
      $this->type = $result['type'];
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
      throw new MyURYException($id.' is not a valid Officer ID!', 400);
    }
    if (!isset(self::$objs[$id])) {
      self::$objs[$id] = new self($id);
    }
    return self::$objs[$id];
  }
  
  /**
   * Returns all the Aliases available.
   * @return array
   */
  public static function getAllOfficerPositions() {
    return self::resultSetToObjArray(self::$db->fetch_column(
            'SELECT officerid FROM public.officer'));
  }
  
  /**
   * Get the ID fo this Officer
   * @return int
   */
  public function getID() {
    return $this->officerid;
  }
  
  /**
   * 
   * @return String
   */
  public function getName() {
    return $this->name;
  }
  
  /**
   * Gets the Officer primary email alias.
   * @return String
   */
  public function getAlias() {
    return $this->alias;
  }
  
  /**
   * @todo
   * @return int|MyURY_Team
   */
  public function getTeam() {
    return $this->team;
  }
  
  /**
   * 
   * @return int
   */
  public function getOrdering() {
    return $this->ordering;
  }
  
  /**
   * 
   * @return String
   */
  public function getDescription() {
    return $this->description;
  }
  
  /**
   * (c)urrent or (h)istorical.
   * @return char
   */
  public function getStatus() {
    return $this->status;
  }
  
  /**
   * (o)fficer, (a)ssistant head of team, (h)ead of team
   * or (m)ember (not actually an Officer, just in team)
   * @return char
   */
  public function getType() {
    return $this->type;
  }
  
  /**
   * Returns data about the Officer.
   * 
   * @todo User who holds or has held position
   * @param bool $full If true, includes info about User who holds position.
   * @return Array
   */
  public function toDataSource($full = true) {
    $data = [
        'officerid' => $this->getID(),
        'name' => $this->getName(),
        'alias' => $this->getAlias(),
        'team' => CoreUtils::dataSourceParser($this->getTeam(), false),
        'ordering' => $this->getOrdering(),
        'description' => $this->getDescription(),
        'status' => $this->getStatus(),
        'type' => $this->getType()
    ];
    
    return $data;
  }

}
