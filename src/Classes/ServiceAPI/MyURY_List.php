<?php
/**
 * This file provides the List class for MyURY
 * @package MyURY_Core
 */

/**
 * The List class stores and manages information about a URY Mailing List
 * 
 * @version 20130526
 * @author Lloyd Wallis <lpw@ury.org.uk>
 * @package MyURY_Core
 * @uses \Database
 */
class MyURY_List extends ServiceAPI {
  /**
   * @var MyURY_List 
   */
  private static $lists = array();
  
  /**
   * Stores the primary key for the list
   * @var int
   */
  private $listid;
  /**
   * Stores the user-friendly name of the list
   * @var String
   */
  private $name;
  /**
   * If non-optin, stores the SQL query that returns the member memberids
   * @var String
   */
  private $sql;
  /**
   * If true, this mailing list has an @ury.org.uk alias that is publically usable
   * @var boolean
   */
  private $public;
  /**
   * If public, this is the prefix for the email address (i.e. "cactus") would be cactus@ury.org.uk
   * @var String
   */
  private $address;
  /**
   * If true, this means that members subscribe themselves to this list
   * @var boolean
   */
  private $optin;
  /**
   * This is the set of members that receive messages to this list
   * @var User[]
   */
  private $members = array();
  
  /**
   * Initiates the MyURY_List object
   * @param int $listid The ID of the Mailing List to initialise
   */
  private function __construct($listid) {
    $this->listid = $listid;
    
    $result = self::$db->fetch_one('SELECT * FROM mail_list WHERE listid=$1', array($listid));
    if (empty($result)) {
      throw new MyURYException('List '.$listid.' does not exist!');
      return null;
    }
    
    $this->name = $result['listname'];
    $this->sql = $result['defn'];
    $this->public = $result['toexim'];
    $this->address = $result['listaddress'];
    $this->optin = $result['subscribable'];
    
    if ($this->optin) {
      //Get subscribed members
      $r = self::$db->fetch_column('SELECT memberid FROM mail_subscription WHERE listid=$1', array($listid));
    } else {
      //Get members joined with opted-out members
      $r = self::$db->fetch_column('SELECT memberid FROM ('.$this->sql.') as t1 WHERE memberid NOT IN
        (SELECT memberid FROM mail_subscription WHERE listid=$1)', array($listid));
    }
    
    foreach ($r as $memberid) {
      $this->members[] = User::getInstance($memberid);
    }
  }
  
  public static function getInstance($listid = -1) {
    self::__wakeup();
    if (!is_numeric($listid)) {
      throw new MyURYException('Invalid List ID!');
    }

    if (!isset(self::$lists[$listid])) {
      self::$lists[$listid] = new self($listid);
    }

    return self::$lists[$listid];
  }
  
  public function getMembers() {
    return $this->members;
  }
  
  public function getID() {
    return $this->listid;
  }
  
  public function getName() {
    return $this->name;
  }
  
  /**
   * Returns if the user has permission to email this list
   * @param User $user
   * @return boolean
   */
  public function hasSendPermission(User $user) {
    if (!$this->public && !$user->hasAuth(AUTH_MAILALLMEMBERS)) return false;
    return true;
  }
  
  public static function getByName($str) {
    $r = self::$db->fetch_column('SELECT listid FROM mail_list WHERE listname ILIKE $1 OR listaddress ILIKE $1',
            array($str));
    if (empty($r)) throw new MyURYException($str.' is not a valid Mailing List');
    else return self::getInstance($r[0]);
  }
}
