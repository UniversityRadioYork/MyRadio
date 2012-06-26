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
  private $fname;
  private $sname;
  private $sex;
  private $email;
  private $collegeid;
  private $college;
  private $phone;
  private $receive_email;
  private $local_name;
  private $local_alias;
  private $eduroam;
  private $account_locked;
  private $studio_trained;
  private $studio_demoed;
  private $joined;
  
  /**
   * Initiates the User variables
   * @param int $memberid The ID of the member to initialise
   */
  private function __construct($memberid) {
    $this->memberid = $memberid;
    //Get the base data
    $data = self::$db->fetch_one(
            'SELECT fname, sname, sex, college AS collegeid, l_college.descr AS college, phone, email,
              receive_email, local_name, local_alias, eduroam, account_locked, joined 
              FROM member, l_college
              WHERE memberid=$1 
              AND member.college = l_college.collegeid
              LIMIT 1',
            array($memberid));
    if (empty($data)) {
      //This user doesn't exist
      throw new MyURYException('The specified User does not appear to exist.');
      return;
    }
    //Set the variables
    foreach ($data as $key => $value) {
      if (filter_var($value, FILTER_VALIDATE_INT)) $this->$key = (int)$value;
      elseif ($value === 't') $this->$key = true;
      elseif ($value === 'f') $this->$key = false;
      else $this->$key = $value;
    }
    
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
   * Returns the User's first name
   * @return string The User's first name 
   */
  public function getFName() {
    return $this->fname;
  }
  
  /**
   * Returns the User's surname
   * @return string The User's surname 
   */
  public function getSName() {
    return $this->sname;
  }
  
  /**
   * Returns the User's full name as one string
   * @return string The User's name 
   */
  public function getName() {
    return $this->fname . ' ' . $this->sname;
  }
  
  /**
   * Returns the User's sex
   * @return string The User's sex 
   */
  public function getSex() {
    return $this->sex;
  }
  
  /**
   * Returns the User's email address
   * @return string The User's email 
   */
  public function getEmail() {
    return $this->email;
  }
  
  /**
   * Returns the User's college id
   * @return int The User's college id
   */
  public function getCollegeID() {
    return $this->collegeid;
  }
  
  /**
   * Returns the User's college name
   * @return string The User's college
   */
  public function getCollege() {
    return $this->college;
  }
  
  /**
   * Returns the User's phone number
   * @return int The User's phone
   */
  public function getPhone() {
    return $this->phone;
  }
  
  /**
   * Returns if the User is set to recive email
   * @return bool if receive_email is set 
   */
  public function getReceiveEmail() {
    return $this->receive_email;
  }
  
  /**
   * Returns the User's local server account
   * @return string The User's local_name
   */
  public function getLocalName() {
    return $this->local_name;
  }
  
  /**
   * Returns the User's email alias
   * @return string The User's local_alias
   */
  public function getLocalAlias() {
    return $this->local_alias;
  }
  
  /**
   * Returns the User's uni account
   * @return string The User's uni email
   */
  public function getUniAccount() {
    return $this->eduroam;
  }
  
  /**
   * Returns if the user's account is locked
   * @return bool if the account is locked
   */
  public function getAccountLocked() {
    return $this->account_locked;
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
  
  public static function findByName($name, $limit) {
    //If there's a space, split into first and last name
    $name = trim($name);
    $names = explode(' ', $name);
    if (isset($names[1])) {
      return self::$db->fetch_all('SELECT memberid, fname, sname FROM member
      WHERE fname ILIKE $1 || \'%\' AND sname ILIKE $2 || \'%\'
      ORDER BY sname, fname LIMIT $3',
            array($names[0], $names[1], $limit));
    } else {
      return self::$db->fetch_all('SELECT memberid, fname, sname FROM member
      WHERE fname ILIKE $1 || \'%\' OR sname ILIKE $1 || \'%\'
      ORDER BY sname, fname LIMIT $2',
            array($name, $limit));
    }
  }
  
  public function getTimeline() {
    $events = array();
    
    //Get their officership history
    $result = self::$db->fetch_all(
      'SELECT \'Got Elected as \' || officer_name AS message, from_date AS timestamp,
      \'photo_officership_get\' AS photo
      FROM member_officer, officer WHERE member_officer.officerid = officer.officerid
      AND memberid=$1
      UNION
      SELECT \'Stepped Down as \' || officer_name AS message, till_date AS timestamp,
      \'photo_officership_down\' AS photo
      FROM member_officer, officer WHERE member_officer.officerid = officer.officerid
      AND memberid=$1 AND till_date IS NOT NULL
      ORDER BY timestamp DESC', array($this->memberid));
    
    foreach ($result as $officership) {
      $events[] = array(
        'timestamp' => $officership['timestamp'],
        'message' => $row['member'],
        'photo' => Config::$$row['photo']
      );
    }
    
    //Get when they joined URY
    $events[] = array(
        'timestamp' => $this->joined,
        'message' => 'Joined URY',
        'photo' => Config::$photo_joined
    );
    
    return $events;
  }
}
