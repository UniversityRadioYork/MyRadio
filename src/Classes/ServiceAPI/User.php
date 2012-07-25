<?php
/**
 * This file provides the User class for MyURY
 * @package MyURY_Core
 */

/**
 * The user object provides and stores information about a user
 * It is not a singleton for Impersonate purposes
 * 
 * @version 09062012
 * @author Lloyd Wallis <lpw@ury.org.uk>
 * @package MyURY_Core
 * @uses \Database
 * @uses \CacheProvider
 */
class User extends ServiceAPI {
  /**
   * Stores User Singletons
   * @var User
   */
  private static $users = array();
  /**
   * Stores the user's memberid
   * @var int
   */
  private $memberid;
  /**
   * Stores the User's permissions
   * @var Array
   */
  private $permissions;
  /**
   * Stores the User's first name
   * @var String
   */
  private $fname;
  /**
   * Stores the User's last name
   * @var String
   */
  private $sname;
  /**
   * Stores the User's gender (either 'm' or 'f')
   * @var String
   */
  private $sex;
  /**
   * Stores the User's preferred contact address
   * @var String
   */
  private $email;
  /**
   * Stores the ID of the User's college
   * @var int
   */
  private $collegeid;
  /**
   * Stores the String name of the User's college
   * @var String
   */
  private $college;
  /**
   * Stores the User's phone number
   * @var String
   */
  private $phone;
  /**
   * Stores whether the User wants to receive email
   * @var bool
   */
  private $receive_email;
  /**
   * Stores the User's username on internal servers, if they have one
   * @var String
   */
  private $local_name;
  /**
   * Stores the User's internal email alias, if they have one.
   * The mail server actually uses this to calculate the values.
   * @var String
   */
  private $local_alias;
  /**
   * Stores the User's eduroam ID
   * @var String
   */
  private $eduroam;
  /**
   * Stores whether or not the account is locked out and cannot be used
   * @var bool
   */
  private $account_locked;
  /**
   * Stores whether the User has been studio trained
   * @var bool
   */
  private $studio_trained;
  /**
   * Stores whether the User has been studio demoed
   * @var bool
   */
  private $studio_demoed;
  /**
   * Stores the time the User joined URY
   * @var int
   */
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
      if ($key === 'joined') $this->$key = (int)strtotime($value);
      elseif (filter_var($value, FILTER_VALIDATE_INT)) $this->$key = (int)$value;
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
    
    //Get the user's training status
    $this->studio_trained = (bool)(self::$db->num_rows(self::$db->query('SELECT completeddate FROM public.member_presenterstatus
      WHERE memberid=$1 AND presenterstatusid=1
      AND memberpresenterstatusid > (SELECT memberpresenterstatusid FROM public.member_presenterstatus
        WHERE presenterstatusid=10 AND memberid=$1
        UNION SELECT 0) LIMIT 1',
            array($this->memberid))) === 1);
    
    //Get the user's demoed status
    $this->studio_trained = (bool)(self::$db->num_rows(self::$db->query('SELECT completeddate FROM public.member_presenterstatus
      WHERE memberid=$1 AND presenterstatusid=2
      AND memberpresenterstatusid > (SELECT memberpresenterstatusid FROM public.member_presenterstatus
        WHERE presenterstatusid=9 AND memberid=$1
        UNION SELECT 0) LIMIT 1',
            array($this->memberid))) === 1);
  }
  
  /**
   * Returns if the user is Studio Trained
   * @return boolean
   */
  public function isStudioTrained() {
    return $this->studio_trained;
  }
  
  /**
   * Returns if the user is Studio Demoed
   * @return boolean
   */
  public function isStudioDemoed() {
    return $this->studio_demoed;
  }
  
  /**
   * @todo Write this
   * @return boolean
   */
  public function hasShow() {
    return true;
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
  
  /**
   * Returns the Singleton User instance of the given memberid, creating it if necessary
   * @param int $memberid The ID of the User to return
   * @return \User
   */
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
  
  /**
   * Searches for Users with a name starting with $name
   * @param String $name The name to search for. If there is a space, it is assumed the second word is the surname
   * @param int $limit The maximum number of Users to return
   * @return Array A 2D Array where every value of the first dimension is an Array as follows:<br>
   * memberid: The unique id of the User<br>
   * fname: The actual first name of the User<br>
   * sname: The actual last name of the User
   */
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
  
  /**
   * Runs a super-long pSQL query that returns the information used to generate the Profile Timeline
   * @return Array A 2D Array where every value of the first dimension is an Array as follows:<br>
   * timestamp: When the event occurred, formatted as d/m/Y<br>
   * message: A text description of the event<br>
   * photo: The photoid of a thumbnail to render with the event
   */
  public function getTimeline() {
    $events = array();
    
    //Get their officership history, show history and awards
    $result = self::$db->fetch_all(
      'SELECT \'got Elected as \' || officer_name AS message, from_date AS timestamp,
        \'photo_officership_get\' AS photo
      FROM member_officer, officer WHERE member_officer.officerid = officer.officerid
      AND memberid=$1
      UNION
      SELECT \'stepped Down as \' || officer_name AS message, till_date AS timestamp,
        \'photo_officership_down\' AS photo
      FROM member_officer, officer WHERE member_officer.officerid = officer.officerid
      AND memberid=$1 AND till_date IS NOT NULL
      UNION
      SELECT message, t1.timestamp, \'photo_show_get\' AS photo FROM
        (SELECT \'was on \' || sched_entry.summary AS message, sched_entry.entryid
        FROM sched_entry, sched_memberentry
        WHERE sched_entry.entryid = sched_memberentry.entryid
        AND entrytypeid = 3
        AND sched_memberentry.memberid = $1
        AND sched_entry.entryid IN
          (SELECT entryid FROM sched_timeslot)
        ) AS t0
        LEFT JOIN (SELECT entryid, min(starttime) AS timestamp FROM sched_timeslot
          GROUP BY entryid
          ORDER BY timestamp ASC) AS t1 ON (t1.entryid = t0.entryid)
       
      UNION
      SELECT \'won an award: \' || name AS message, awarded AS timestamp,
        \'photo_award_get\' AS photo
      FROM myury.award_categories, myury.award_member
      WHERE myury.award_categories.awardid = myury.award_member.awardid
      AND memberid = $1
      
      ORDER BY timestamp DESC', array($this->memberid));
    
    foreach ($result as $row) {
      $events[] = array(
        'timestamp' => date('d/m/Y',strtotime($row['timestamp'])),
        'message' => $row['message'],
        'photo' => Config::$$row['photo']
      );
    }
    
    //Get when they joined URY
    $events[] = array(
        'timestamp' => date('d/m/Y',strtotime($this->joined)),
        'message' => 'Joined URY',
        'photo' => Config::$photo_joined
    );
    
    return $events;
  }
}
