<?php
/**
 * The user object provides and stores information about a user
 * It is not a singleton for Impersonate purposes
 * @version 09062012
 * @author Lloyd Wallis <lpw@ury.york.ac.uk>
 */
class User extends ServiceAPI {
  private static $users = array();
  private $memberid;
  private $permissions;
  private $name;
  private $email;
  private $college;
  private $phone;
  private $receive_email;
  private $local_name;
  private $account_locked;
  private $studio_trained;
  private $studio_demoed;
  
  /**
   * Initiates the User variables
   * @param int $memberid The ID of the member to initialise
   */
  private function __construct($memberid) {
    $this->memberid = $memberid;
    //Get the base data
    $data = self::$db->fetch_one(
            'SELECT fname || sname AS name, college, phone, email,
              receive_email, local_name, account_locked FROM member
              WHERE memberid=$1 LIMIT 1',
            array($memberid));
    if (empty($data)) {
      //This user doesn't exist
      throw new MyURYException('The specified User does not appear to exist.');
      return;
    }
    //Set the variables
    foreach ($data as $key => $value) $this->$key = $value;
    
    //Get the user's permissions
    $this->permissions = self::$db->fetch_column('SELECT lookupid FROM auth_officer
      WHERE officerid IN (SELECT officerid FROM member_officer
        WHERE memberid=$1 AND from_date < now()- interval \'1 month\' AND
        (till_date IS NULL OR till_date > now()- interval \'1 month\'))',
            array($memberid));
    
    //Get the user's training and demoed status
    /**
     * @todo this bit 
     */
  }
  
  /**
   * Returns the User's memberid
   * @return int The User's memberid
   */
  public function getID() {
    return $this->memberid;
  }
  
  /**
   * Returns if the user has the given permission
   * @param int $authid The permission to test for
   * @return boolean Whether this user has the requested permission 
   */
  public function hasAuth($authid) {
    return (in_array($authid, $this->permissions));
  }
  
  public static function getInstance($memberid = -1) {
    self::__wakeup();
    //Check the input is an int, and use the session user if not otherwise told
    $memberid = (int) $memberid;
    if ($memberid === -1) $memberid = $_SESSION['memberid'];
    
    //Check if a user class already exists for this memberid
    //(Each memberid-user combination should only have one initiated instance)
    if (isset(self::$users[$memberid])) return self::$user[$memberid];
    
    //Return the object if it is cached
    $entry = self::$cache->get('MyURYUser_'.$memberid);
    if ($entry === false) {
      //Not cached.
      $entry = new User($memberid);
      self::$cache->set('MyURYUser_'.$memberid, $entry, 3600);
    } else {
      //Wake up the object
      $entry->__wakeup();
    }
    
    return $entry;
  }
}
