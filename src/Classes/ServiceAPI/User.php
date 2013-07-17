<?php
/**
 * This file provides the User class for MyURY
 * @package MyURY_Core
 */

/**
 * The user object provides and stores information about a user
 * It is not a singleton for Impersonate purposes
 * 
 * @version 20130716
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
   * Stores payment information about the User
   * @var array
   */
  private $payment;
  /**
   * Stores all the User's officerships
   * @var array
   */
  private $officerships;
  /**
   * Stores all the training data / status of the user
   * @var array
   */
  private $training;
  /**
   * Stores the time the User joined URY
   * @var int
   */
  private $joined;
  /**
   * Stores the datetime the User last logged in on.
   * @var String timestamp with timezone
   */
  private $lastlogin;

  /**
   * Initiates the User variables
   * @param int $memberid The ID of the member to initialise
   */
  private function __construct($memberid) {
    $this->memberid = $memberid;
    //Get the base data
    $data = self::$db->fetch_one(
            'SELECT fname, sname, sex, college AS collegeid, l_college.descr AS college, phone, email,
              receive_email, local_name, local_alias, eduroam, account_locked, last_login, joined 
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
    
    $this->payment = self::$db->fetch_all('SELECT year, paid 
      FROM member_year 
      WHERE memberid = $1 
      ORDER BY year ASC;',
            array($memberid));
    
    // Get the User's officerships
    $this->officerships = self::$db->fetch_all('SELECT officerid,officer_name,teamid,from_date,till_date
			 FROM member_officer 
       INNER JOIN officer 
       USING (officerid) 
       WHERE memberid = $1 
       ORDER BY from_date,till_date;', 
            array($memberid));
    
    // Get Training info all into array
    $this->training = self::$db->fetch_all('SELECT presenterstatusid, completeddate, confirmedby, mem.fname||\' \'||mem.sname AS confirmedname
      FROM public.member_presenterstatus pres
      INNER JOIN member mem ON (confirmedby = mem.memberid)
      WHERE pres.memberid=$1
      ORDER BY completeddate ASC', 
            array($this->memberid));
    
  }
  
  
  public function getData() {
    return get_object_vars($this);
  }
  
  /**
   * Returns if the user is Studio Trained
   * @return boolean
   */
  public function isStudioTrained() {
    $trained = false;
    foreach($this->training as $key => $value){
      if ($value['presenterstatusid'] == 1) {
        $trained = true;
      }
      if ($value['presenterstatusid'] == 10) {
        $trained = false;
      }
    }
    return $trained;
  }
  
  /**
   * Returns the last User that trained this User. Still returns them if the Training status was revoked.
   * @return User|null
   */
  public function getStudioTrainedBy() {
    $trainer = null;
    foreach ($this->training as $value) {
      if ($value['presenterstatusid'] == 1) {
        $trainer = User::getInstance($value['confirmedby']);
      }
    }
    
    return $trainer;
  }
  
  /**
   * Returns if the user is Studio Demoed
   * @return boolean
   */
  public function isStudioDemoed() {
    $demoed = false;
    foreach($this->training as $key => $value){
      if ($value['presenterstatusid'] == 2) {
        $demoed = true;
      }
      if ($value['presenterstatusid'] == 9) {
        $demoed = false;
      }
    }
    return $demoed;
  }
  
  /**
   * Returns the last User that demoed this User. Still returns them if the Demoed status was revoked.
   * @return User|null
   */
  public function getStudioDemoedBy() {
    $trainer = null;
    foreach ($this->training as $value) {
      if ($value['presenterstatusid'] == 2) {
        $trainer = User::getInstance($value['confirmedby']);
      }
    }
    
    return $trainer;
  }
  
  /**
   * Returns if the user is a Trainer
   * @return boolean
   */
  public function isTrainer() {
    $trainer = false;
    foreach($this->training as $key => $value){
      if ($value['presenterstatusid'] == 3) {
        $trainer = true;
      }
      if ($value['presenterstatusid'] == 11) {
        $trainer = false;
      }
    }
    return $trainer;
  }
  
  /**
   * Returns the last User that trainer trained this User. Still returns them if the Trainer status was revoked.
   * @return User|null
   */
  public function getTrainerTrainedBy() {
    $trainer = null;
    foreach ($this->training as $value) {
      if ($value['presenterstatusid'] == 3) {
        $trainer = User::getInstance($value['confirmedby']);
      }
    }
    
    return $trainer;
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
   * Returns the User's email address. If the email address is null, it is assumed their eduroam address is the
   * preferred contact method.
   * @return string The User's email 
   */
  public function getEmail() {
    return empty($this->email) ? $this->getEduroam() : $this->email;
  }
  
  /**
   * Returns the User's eduroam ID, i.e. their @york.ac.uk email address.
   * @return String
   */
  public function getEduroam() {
    return $this->eduroam;
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
    return in_array($authid, $this->permissions);
  }
  
  /**
   * Returns the Singleton User instance of the given memberid, creating it if necessary
   * @param int $memberid The ID of the User to return
   * @return \User
   */
  public static function getInstance($memberid = -1) {
    //__wakeup isn't static.
    self::initCache(); self::initDB();
    //Check the input is an int, and use the session user if not otherwise told
    $memberid = (int) $memberid;
    if ($memberid === -1) {
      if (!isset($_SESSION)) $memberid = 779; //Mr Website
      else $memberid = $_SESSION['memberid'];
    }
    
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
  
  /**
   * Searched for the user with the given email address, returning the User if they exist, or null if it fails.
   * @param String $email
   * @return null|User
   */
  public static function findByEmail($email) {
    if (empty($email)) return null;
    self::wakeup();
    
    $result = self::$db->fetch_column('SELECT memberid FROM public.member WHERE email ILIKE $1 OR eduroam ILIKE $1
      OR local_name ILIKE $2 OR local_alias ILIKE $2', array($email, explode('@',$email)[0]));
    
    if (empty($result)) return null;
    else return self::getInstance($result[0]);
  }
  
  public static function findAllTrained() {
    self::wakeup();
    
    $trained = self::$db->fetch_column('SELECT memberid FROM public.member_presenterstatus WHERE presenterstatusid=1');
    $members = array();
    foreach ($trained as $mid) {
      $member = User::getInstance($mid);
      if ($member->isStudioTrained()) $members[] = $member;
    }
    
    return $members;
  }
  
  public static function findAllDemoed() {
    self::wakeup();
    
    $trained = self::$db->fetch_column('SELECT memberid FROM public.member_presenterstatus WHERE presenterstatusid=2');
    $members = array();
    foreach ($trained as $mid) {
      $member = User::getInstance($mid);
      if ($member->isStudioDemoed()) $members[] = $member;
    }
    
    return $members;
  }
  
  public static function findAllTrainers() {
    self::wakeup();
    
    $trained = self::$db->fetch_column('SELECT memberid FROM public.member_presenterstatus WHERE presenterstatusid=3');
    $members = array();
    foreach ($trained as $mid) {
      $member = User::getInstance($mid);
      if ($member->isTrainer()) $members[] = $member;
    }
    
    return $members;
  }
  
  /**
   * Gets the edit form for this User, with the permissions available for the current User
   */
  public function getEditForm() {
    if ($this !== User::getInstance() && !User::getInstance()->hasAuth(AUTH_EDITANYPROFILE)) {
      throw new MyURYException(User::getInstance().' tried to edit '.$this.'!');
    }
    
    $form = new MyURYForm('profileedit', 'Profile', 'doEdit', array('title' => 'Edit Profile'));
    //Personal details
    $form->addField(new MyURYFormField('sec_personal', MyURYFormField::TYPE_SECTION,
            array(
                'label' => 'Personal Details'
            )))
            ->addField(new MyURYFormField('fname', MyURYFormField::TYPE_TEXT,
            array(
                'required' => true,
                'label' => 'First Name',
                'value' => $this->getFName()
            )))
            ->addField(new MyURYFormField('sname', MyURYFormField::TYPE_TEXT,
            array(
                'required' => true,
                'label' => 'Last Name',
                'value' => $this->getSName()
            )))
            ->addField(new MyURYFormField('gender', MyURYFormField::TYPE_SELECT,
            array(
                'required' => true,
                'label' => 'Gender',
                'value' => $this->getSex(),
                'options' => array(
                    array('value' => 'm', 'text' => 'Male'),
                    array('value' => 'f', 'text' => 'Female'),
                    array('value' => 'o', 'text' => 'Other')
                )
            )));
    
    //Contact details
    $form->addField(new MyURYFormField('sec_contact', MyURYFormField::TYPE_SECTION,
            array(
                'label' => 'Contact Details'
            )))
            ->addField(new MyURYFormField('college', MyURYFormField::TYPE_SELECT,
            array(
               'required' => true,
               'label' => 'College',
               'options' => self::getColleges(),
               'value' => $this->getCollegeID()
            )))
            ->addField(new MyURYFormField('phone', MyURYFormField::TYPE_TEXT,
            array(
                'required' => false,
                'label' => 'Phone Number',
                'value' => $this->getPhone()
            )))
            ->addField(new MyURYFormField('email', MyURYFormField::TYPE_EMAIL,
            array(
                'required' => true,
                'label' => 'Email',
                'value' => $this->getEmail()
            )))
            ->addField(new MyURYFormField('receive_email', MyURYFormField::TYPE_CHECK,
            array(
                'required' => false,
                'label' => 'Receive Email?',
                'options' => array('checked' => $this->getReceiveEmail()),
                'explanation' => 'If unchecked, you will receive no emails, even if you are subscribed to mailing lists.'
            )))
            ->addField(new MyURYFormField('eduroam', MyURYFormField::TYPE_TEXT,
            array(
                'required' => false,
                'label' => 'University Email',
                'value' => str_replace('@york.ac.uk','',$this->getUniAccount()),
                'explanation' => '@york.ac.uk'
            )));
    
    //Mailbox
    if (User::getInstance()->hasAuth(AUTH_CHANGESERVERACCOUNT)) {
      $form->addField(new MyURYFormField('sec_server', MyURYFormField::TYPE_SECTION,
              array(
                  'label' => 'URY Mailbox Account',
                  'explanation' => 'Before changing these settings, please ensure you understand the guidelines and'
                  .' documentation on URY\'s Internal Email Service'
              )))
            ->addField(new MyURYFormField('local_name', MyURYFormField::TYPE_TEXT,
            array(
                'required' => false,
                'label' => 'Server Account (Mailbox)',
                'value' => $this->getLocalName(),
                'explanation' => 'Best practice is their ITS Username'
            )))
            ->addField(new MyURYFormField('local_alias', MyURYFormField::TYPE_TEXT,
            array(
                'required' => false,
                'label' => '@ury.org.uk Alias',
                'value' => $this->getLocalAlias(),
                'explanation' => 'Usually, this is firstname.lastname (i.e. '.
                strtolower($this->getFName().'.'.$this->getSName()).')'
            )));
    }
            
    
    return $form;
  }
  
  public static function getColleges() {
    return self::$db->fetch_all('SELECT collegeid AS value, descr AS text FROM public.l_college');
  }
  
  /**
   * Create a new User, returning the user.
   * 
   * At least one of Email OR eduroam must be filled in. Password will be generated
   * automatically and emailed to the user.
   * 
   * @param array $params As follows:
   * collegeid: Optional. College ID of the member. Default 10 (Unknown).
   * eduroam: Optional. User's @york.ac.uk email address, if they have one.
   * email: Optional. The User's email address. If not set, eduroam is used.
   * fname: Required. The User's first name.
   * phone: Optional. The User's phone number.
   * receive_email: Optional. Default true. Whether or not the user will receive emails
   * sex: Required. 'm' Male, 'f' Female or 'o' Other.
   * sname: Required. The User's last name.
   * paid: Optional. How much the user has paid for the current membership year. Default 0.00.
   */
  public static function create($params) {
    CoreUtils::requirePermission(AUTH_ADDMEMBER);
    //Validate input
    if (empty($params['collegeid'])) {
      $params['collegeid'] = Config::$default_college;
    } elseif (!is_numeric($params['collegeid'])) {
      throw new MyURYException('Invalid College ID!', 400);
    }
    
    if (empty($params['eduroam']) && empty($params['email'])) {
      throw new MyURYException('At least one of eduroam or email must be provided.', 400);
    } elseif (empty($params['email'])) {
      $params['email'] = null;
    } elseif (empty($params['eduroam'])) {
      $params['eduroam'] = null;
    }
    
    if (empty($params['fname'])) {
      throw new MyURYException('User must have a first name!', 400);
    }
    
    if (empty($params['phone'])) {
      $params['phone'] = null;
    }
    
    if (empty($params['receive_email'])) {
      $params['receive_email'] = true;
    }
    
    if (empty($params['sex'])) {
      throw new MyURYException('User must have a gender!');
    } elseif ($params['sex'] !== 'm' && $params['sex'] !== 'f' && $params['sex'] !== 'o') {
      throw new MyURYException('User gender must be m, f or o!', 400);
    }
    
    if (empty($params['sname'])) {
      throw new MyURYException('User must have a last name!', 400);
    }
    
    if (empty($params['paid'])) {
      $params['paid'] = 0.00;
    } elseif (!is_numeric($params['paid'])) {
      throw new MyURYException('Invalid payment amount!', 400);
    }
    
    //Check if it looks like the user might already exist
    if (!empty(User::findByEmail($params['eduroam'])) or !empty(User::findByEmail($params['email']))) {
      print_r(User::findByEmail($params['email']));
      throw new MyURYException('This user already appears to exist. Their eduroam or email is already used.');
    }
    
    //This next comment explains that password generation is not done in MyURY itself, but an external library.
    //Looks good. Generate a password for them. This is done by Shibbobleh.
    $plain_pass = Shibbobleh_Utils::newPassword();
    $encrypted_pass = Shibbobleh_Utils::encrypt($plain_pass);
    
    //Actually create the member!
    $r = self::$db->fetch_column('INSERT INTO public.member
      (fname, sname, sex, college, phone, email, receive_email, password, eduroam, require_password_change)
      VALUES
      ($1, $2, $3, $4, $5, $6, $7, $8, $9) RETURNING memberid', array(
          $params['fname'],
          $params['sname'],
          $params['sex'],
          $params['collegeid'],
          $params['phone'],
          $params['email'],
          $params['receive_email'],
          $encrypted_pass,
          $params['eduroam'],
          true
      ));
    
    $memberid = $r[0];
    //Activate the member's account for the current academic year
    Shibbobleh_Utils::activateMemberThisYear($memberid, $params['paid']);
    
    //Send a welcome email (this will not send if receive_email is not enabled!)
    /**
     * @todo Make this easier to change
     * @todo Link to Facebook events
     */
    $uname = empty($params['eduroam']) ? $params['email'] : str_replace('@york.ac.uk','',$params['eduroam']);
    $welcome_email = <<<EOT
<p>Hi $fname!</p>

<p>Thanks for showing an interest in URY, your official student radio station.</p>

<p>My name's Lloyd, and I'm the Head of Training here. It's my job to make it as easy as possible to get on the air or
join any of our other teams.</p>

<p>Coming up in Week 2 we have two Get Involved sessions where we tell you about all the things we do and how you can
join in. We've also got a live event straight after so you can see us in action!</p>

<ul>
  <li>7pm Tuesday Week 2 (8th October), RCH/037 (Heslington East), followed by a live panel show broadcast in The Glasshouse.</li>
  <li>7pm Thursday Week 2 (10th October), V/045 (Vanbrugh College), followed by a live session show broadcast in The Courtyard.</li>
</ul>

<p>We'll also be giving away free entry, queue jumps and a bottle of champagne for up to 10 people for Kuda on Tuesday and Tokyo on Thursday in our Warm Up shows.</p>

<p>Finally, URY has a lot of <a href="https://ury.org.uk/myury/">online resources</a> that are useful for all sorts of things, so you'll need your login details:</p>
<p>Username: $uname<br>
Password: $plain_pass</p>

<p>If you have any questions, feel free to ask by emailing <a href="mailto:training@ury.org.uk">training@ury.org.uk</a>.</p>

Hope to see you soon.
<br><br>
--<br>
Lloyd Wallis<br>
Head of Training<br>
<br>
University Radio York 1350AM<br>
Silver Best Student Radio Station 2012<br>
---------------------------------------------<br>
07968011154 <a href="mailto:lloyd.wallis@ury.org.uk">lloyd.wallis@ury.org.uk</a><br>
---------------------------------------------<br>
On Air | Online | On Demand<br>
<a href="http://ury.org.uk/">ury.org.uk</a>
EOT;
    
    //Send the email
    MyURYEmail::create(User::getInstance($memberid), 'Welcome to URY - Getting Involved and Your Account',
            $welcome_email, User::getInstance(7449));
    
    return User::getInstance($memberid);
  }
}