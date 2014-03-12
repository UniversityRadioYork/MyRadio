<?php

/**
 * This file provides the User class for MyRadio
 * @package MyRadio_Core
 */

/**
 * The user object provides and stores information about a user
 * It is not a singleton for Impersonate purposes
 *
 * @version 20130824
 * @author Lloyd Wallis <lpw@ury.org.uk>
 * @package MyRadio_Core
 * @uses \Database
 * @uses \CacheProvider
 */
class MyRadio_User extends ServiceAPI
{
    /**
     * Stores the currently logged in User's object after first use.
     */
    private static $current_user;
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
    private $last_login;

    /**
     * Photoid of the User's profile photo
     * @var int
     */
    private $profile_photo;

    /**
     * Stores a User's biography. HTML, so ensure you | raw if outputting!
     * @var String
     */
    private $bio = '';

    /**
     * Initialised on first request, stores a list of Show IDs the User has.
     *
     * @var int[]
     */
    private $shows;

    /**
     * The Authentication Provider that should be used when logging this user in
     * default Null (any)
     *
     * @var String|null
     */
    private $auth_provider;

    /**
     * If true, this user needs to change their password at next logon.
     *
     * @var boolean
     */
    private $require_password_change;
    
    public function getInstance($memberid = -1) {
        if ($memberid === -1) {
            $memberid = $_SESSION['memberid'];
        }
        if ($memberid == $_SESSION['memberid']) {
            if (!$cuser) {
                $cuser = parent::getInstance($memberid);
            }
            return $cuser;
        } else {
            return parent::getInstance($memberid);
        }
    }

    /**
     * Initiates the User variables
     * @param int $memberid The ID of the member to initialise
     */
    protected function __construct($memberid)
    {
        $this->memberid = (int) $memberid;
        //Get the base data
        $data = self::$db->fetch_one(
            'SELECT fname, sname, sex, college AS collegeid, l_college.descr AS college,
            phone, email, receive_email, local_name, local_alias, eduroam,
            account_locked, last_login, joined, profile_photo, bio,
            auth_provider, require_password_change
            FROM member, l_college
            WHERE memberid=$1
            AND member.college = l_college.collegeid
            LIMIT 1',
            array($memberid)
        );
        if (empty($data)) {
            //This user doesn't exist
            throw new MyRadioException('The specified User does not appear to exist.', 404);
        }
        //Set the variables
        foreach ($data as $key => $value) {
            if ($key === 'joined') {
                $this->$key = (int) strtotime($value);
            } elseif (filter_var($value, FILTER_VALIDATE_INT)) {
                $this->$key = (int) $value;
            } elseif ($value === 't') {
                $this->$key = true;
            } elseif ($value === 'f') {
                $this->$key = false;
            } else {
                $this->$key = $value;
            }
        }

        //Get the user's permissions
        $this->permissions = self::$db->fetch_column(
            'SELECT lookupid FROM auth_officer
            WHERE officerid IN (SELECT officerid FROM member_officer
            WHERE memberid=$1 
            AND from_date <= now()
            AND (till_date IS NULL OR till_date > now()- interval \'1 month\'))
            UNION SELECT lookupid FROM auth 
            WHERE memberid=$1
            AND starttime < now() 
            AND (endtime IS NULL OR endtime >= now())',
            array($memberid)
        );

        $this->payment = self::$db->fetch_all(
            'SELECT year, paid
            FROM member_year
            WHERE memberid = $1
            ORDER BY year ASC;',
            array($memberid)
        );

        // Get the User's officerships
        $this->officerships = self::$db->fetch_all(
            'SELECT officerid,officer_name,teamid,from_date,till_date
            FROM member_officer
            INNER JOIN officer
            USING (officerid)
            WHERE memberid = $1
            AND type!=\'m\'
            ORDER BY from_date,till_date;',
            array($memberid)
        );

        // Get Training info all into array
        $this->training = self::$db->fetch_column(
            'SELECT memberpresenterstatusid
            FROM public.member_presenterstatus LEFT JOIN public.l_presenterstatus USING (presenterstatusid)
            WHERE memberid=$1 ORDER BY ordering, completeddate ASC',
            array($this->memberid)
        );

        if ($this->isCurrentlyPaid()) {
            //Add training permissions, but only if currently paid
            foreach ($this->getAllTraining() as $training) {
                $this->permissions = array_merge($this->permissions, $training->getPermissions());
            }
        }
    }

    /**
     * Returns if the User is currently an Officer
     *
     * @return bool
     */
    public function isOfficer()
    {
        foreach ($this->getOfficerships() as $officership) {
            if (empty($officership['till_date']) or $officership['till_date'] >= time()) {
                return true;
            }
        }

        return false;
    }

    /**
     * Returns if the user is Studio Trained
     * @return boolean
     */
    public function isStudioTrained()
    {
        foreach ($this->getAllTraining(true) as $train) {
            if ($train->getID() == 1) {
                return true;
            }
        }

        return false;
    }

    /**
     * Returns if the user is Studio Demoed
     * @return boolean
     */
    public function isStudioDemoed()
    {
        foreach ($this->getAllTraining(true) as $train) {
            if ($train->getID() == 2) {
                return true;
            }
        }

        return false;
    }

    /**
     * Returns if the user is a Trainer
     * @return boolean
     */
    public function isTrainer()
    {
        foreach ($this->getAllTraining(true) as $train) {
            if ($train->getID() == 3) {
                return true;
            }
        }

        return false;
    }

    /**
     * Get all types of training the User has.
     *
     * @param  bool                              $ignore_revoked If true, Revoked statuses will not be included.
     * @return Array[MyRadio_UserTrainingStatus]
     */
    public function getAllTraining($ignore_revoked = false)
    {
        if ($ignore_revoked) {
            $data = [];
            foreach (MyRadio_UserTrainingStatus::resultSetToObjArray($this->training) as $train) {
                if ($train->getRevokedBy() == null) {
                    $data[] = $train;
                }
            }

            return $data;
        } else {
            return MyRadio_UserTrainingStatus::resultSetToObjArray($this->training);
        }
    }

    /**
     * Returns whether the User has paid the correct amount to be
     * a full member in the current year.
     * @return bool
     */
    public function isCurrentlyPaid()
    {
        foreach ($this->getAllPayments() as $payment) {
            if ($payment['year'] == CoreUtils::getAcademicYear()) {
                return $payment['paid'] >= Config::$membership_fee;
            }
        }

        return false;
    }

    /**
     * Return whether the User is currently has any shows.
     * @return boolean
     */
    public function hasShow()
    {
        return sizeof($this->getShows()) !== 0;
    }

    /**
     * Returns the User's memberid
     * @return int The User's memberid
     */
    public function getID()
    {
        return $this->memberid;
    }

    /**
     * Returns the User's first name
     * @return string The User's first name
     */
    public function getFName()
    {
        return $this->fname;
    }

    /**
     * Returns the User's surname
     * @return string The User's surname
     */
    public function getSName()
    {
        return $this->sname;
    }

    /**
     * Returns the User's full name as one string
     * @return string The User's name
     */
    public function getName()
    {
        return $this->fname . ' ' . $this->sname;
    }

    /**
     * Returns the User's sex
     * @return string The User's sex
     */
    public function getSex()
    {
        return $this->sex;
    }

    public function getLastLogin()
    {
        return $this->last_login;
    }

    /**
     * Returns the User's profile Photo (or null if there is not one)
     * @return MyRadio_Photo
     */
    public function getProfilePhoto()
    {
        if (!empty($this->profile_photo)) {
            return MyRadio_Photo::getInstance($this->profile_photo);
        } else {
            return null;
        }
    }

    /**
     * Returns all the user's active permission flags
     * @return Array
     */
    public function getPermissions()
    {
        return $this->permissions;
    }

    /**
     * Returns the User's email address. If the email address is null, it is
     * assumed their eduroam address is the preferred contact method.
     *
     * This is the address that emails to the User actually *go to*. It is not
     * the address that should be shown to your standard member.
     * See getPublicEmail().
     *
     * @todo hardcoded domains here.
     * @return string The User's email
     */
    public function getEmail()
    {
        if (strstr($this->email, '@ury.org.uk') !== false or strstr($this->email, '@ury.york.ac.uk') !== false) {
            //The user has set an alias or their local mailbox here.
            //Return the local mailbox, or, failing that, eduroam
            $local = $this->getLocalName();
            if (!empty($local)) {
                return $local;
            } else {
                //ffs, some people don't have an eduroam either.
                $eduroam = $this->getEduroam();
                if (empty($eduroam)) {
                    return null;
                } else {
                    return $eduroam . '@'.Config::$eduroam_domain;
                }
            }
        } elseif (empty($this->email)) {
            return $this->getEduroam() . '@'.Config::$eduroam_domain;
        } else {
            return $this->email;
        }
    }

    /**
     * Used for Officers. If they have an @ury.org.uk Alias, display that.
     * Otherwise, display their default email. This is because if a user wants an
     * official @ury.org.uk, but wants it fowarded, then you set the local_alias
     * to the @ury.org.uk prefix, and email to their personal address.
     */
    public function getPublicEmail()
    {
        /**
         * This works around a PHP bug:
         * Fatal error: Can't use method return value in write context is thrown if the getter is used directly in empty()
         */
        $alias = $this->getLocalAlias();

        return empty($alias) ? $this->getEmail() : $alias . '@ury.org.uk';
    }

    /**
     * Returns the User's eduroam ID, i.e. their @york.ac.uk email address.
     * @return String
     */
    public function getEduroam()
    {
        return str_replace('@' . Config::$eduroam_domain, '', $this->eduroam);
    }

    /**
     * Returns the User's college id
     * @return int The User's college id
     */
    public function getCollegeID()
    {
        return $this->collegeid;
    }

    /**
     * Returns the User's college name
     * @return string The User's college
     */
    public function getCollege()
    {
        return $this->college;
    }

    /**
     * Returns the User's phone number
     * @return int The User's phone
     */
    public function getPhone()
    {
        return $this->phone;
    }

    /**
     * Gets every year the member has paid
     */
    public function getAllPayments()
    {
        return $this->payment;
    }

    /**
     * Returns if the User is set to recive email
     * @return bool if receive_email is set
     */
    public function getReceiveEmail()
    {
        return $this->receive_email;
    }

    /**
     * Returns the User's local server account
     * @return string The User's local_name
     */
    public function getLocalName()
    {
        return $this->local_name;
    }

    /**
     * Returns the User's email alias
     * @return string The User's local_alias
     */
    public function getLocalAlias()
    {
        return $this->local_alias;
    }

    /**
     * Returns the User's uni account
     * @return string The User's uni email
     * @todo This is a duplication of getEduroam.
     */
    public function getUniAccount()
    {
        return $this->eduroam;
    }

    /**
     * Returns if the User's account is locked
     * @return bool if the account is locked
     */
    public function getAccountLocked()
    {
        return $this->account_locked;
    }

    /**
     * Get all the User's past, present and future officerships
     */
    public function getOfficerships()
    {
        return $this->officerships;
    }

    /**
     * Gets the User's MyRadio Profile page URL
     * @return String
     */
    public function getURL()
    {
        return CoreUtils::makeURL('Profile', 'view', array('memberid' => $this->getID()));
    }

    /**
     * Gets the User's bio
     * @return String
     */
    public function getBio()
    {
        return $this->bio;
    }

    /**
     * Get the User's auth provider
     *
     * @return String
     */
    public function getAuthProvider()
    {
        return $this->auth_provider;
    }

    /**
     * Get whether the user needs to change their password
     *
     * @return boolean
     */
    public function getRequirePasswordChange()
    {
        return $this->require_password_change;
    }

    /**
     * Returns an array of Shows which the User owns or is an active
     * credit in. Guaranteed order by first broadcast date of the show.
     *
     * @param  int   $show_type_id
     * @return Array an array of Show objects attached to the given user
     */
    public function getShows($show_type_id = 1)
    {
        $this->shows = self::$db->fetch_column(
            'SELECT show_id FROM schedule.show
            WHERE memberid=$1 OR show_id IN
            (SELECT show_id FROM schedule.show_credit
            WHERE creditid=$1 AND effective_from <= NOW() AND
            (effective_to >= NOW() OR effective_to IS NULL))
            ORDER BY (SELECT start_time FROM schedule.show_season_timeslot
            WHERE show_season_id IN
            (SELECT show_season_id FROM schedule.show_season WHERE show_id=schedule.show.show_id)
            ORDER BY start_time LIMIT 1)
            ASC',
            array($this->getID())
        ); //Wasn't that ORDER BY fun.

        $return = array();
        foreach ($this->shows as $show_id) {
            $show = MyRadio_Show::getInstance($show_id);
            if ($show->getShowType() == $show_type_id) {
                $return[] = $show;
            }
        }

        return $return;
    }

    /**
     * Returns if the user has the given permission.
     *
     * Always use CoreUtils::hasAuth when working with the current user.
     *
     * @param  int     $authid The permission to test for
     * @return boolean Whether this user has the requested permission
     */
    public function hasAuth($authid)
    {
        return in_array($authid, $this->permissions);
    }

    /**
     * Searches for Users with a name starting with $name
     * @param  String $name  The name to search for. If there is a space, it is assumed the second word is the surname
     * @param  int    $limit The maximum number of Users to return. -1 uses the ajax_limit_default setting.
     * @return Array  A 2D Array where every value of the first dimension is an Array as follows:<br>
     *                      memberid: The unique id of the User<br>
     *                      fname: The actual first name of the User<br>
     *                      sname: The actual last name of the User
     */
    public static function findByName($name, $limit = -1)
    {
        if ($limit == -1) {
            $limit = Config::$ajax_limit_default;
        }
        //If there's a space, split into first and last name
        $name = trim($name);
        $names = explode(' ', $name);
        if (isset($names[1])) {
            return self::$db->fetch_all(
                'SELECT memberid, fname, sname FROM member
                WHERE fname ILIKE $1 || \'%\' AND sname ILIKE $2 || \'%\'
                ORDER BY sname, fname LIMIT $3',
                array($names[0], $names[1], $limit)
            );
        } else {
            return self::$db->fetch_all(
                'SELECT memberid, fname, sname FROM member
                WHERE fname ILIKE $1 || \'%\' OR sname ILIKE $1 || \'%\'
                ORDER BY sname, fname LIMIT $2',
                array($name, $limit)
            );
        }
    }

    public static function getInstance($itemid = -1)
    {
        if ($itemid === -1) {
            if (isset($_SESSION['memberid'])) {
                $itemid = $_SESSION['memberid'];
            } else {
                throw new MyRadioException('Trying to get current user info with no current user');
            }
        }

        return parent::getInstance($itemid);
    }

    /**
     * Returns the current logged in user, or failing that, the System User.
     *
     * @return MyRadio_User
     */
    public static function getCurrentOrSystemUser()
    {
        if (isset($_SESSION['memberid'])) {
            return self::getInstance();
        } else {
            return self::getInstance(Config::$system_user);
        }
    }

    /**
     * Runs a super-long pSQL query that returns the information used to generate the Profile Timeline
     * @return Array A 2D Array where every value of the first dimension is an Array as follows:<br>
     *               timestamp: When the event occurred, formatted as d/m/Y<br>
     *               message: A text description of the event<br>
     *               photo: The photoid of a thumbnail to render with the event
     */
    public function getTimeline()
    {
        $events = array();

        //Get Officership history
        foreach ($this->getOfficerships() as $officer) {
            $events[] = [
                'message' => 'became ' . $officer['officer_name'],
                'timestamp' => strtotime($officer['from_date']),
                'photo' => Config::$photo_officership_get
            ];
            if ($officer['till_date'] != null) {
                $events[] = [
                    'message' => 'stepped down as ' . $officer['officer_name'],
                    'timestamp' => strtotime($officer['till_date']),
                    'photo' => Config::$photo_officership_down
                ];
            }
        }

        foreach ($this->getShows() as $show) {
            $credit = 'Owner';
            foreach ($show->getCreditsNames(true) as $c) {
                if ($c['name'] === $this->getName()) {
                    $credit = $c['type_name'];
                    break;
                }
            }
            foreach ($show->getAllSeasons() as $season) {
                if (sizeof($season->getAllTimeslots()) === 0) {
                    continue;
                }
                if ($season->getSeasonNumber() == 1) {
                    $events[] = [
                        'message' => 'started a new Show as ' . $credit . ' of ' . $season->getMeta('title'),
                        'timestamp' => strtotime($season->getAllTimeslots()[0]->getStartTime()),
                        'photo' => $show->getShowPhoto()
                    ];
                } else {
                    $events[] = [
                        'message' => 'was ' . $credit . ' on Season ' . $season->getSeasonNumber() . ' of ' . $season->getMeta('title'),
                        'timestamp' => strtotime($season->getAllTimeslots()[0]->getStartTime()),
                        'photo' => $show->getShowPhoto()
                    ];
                }
            }
        }

        //Get their officership history, show history and awards
        /* $result = self::$db->fetch_all(
          SELECT \'won an award: \' || name AS message, awarded AS timestamp,
          \'photo_award_get\' AS photo
          FROM myury.award_categories, myury.award_member
          WHERE myury.award_categories.awardid = myury.award_member.awardid
          AND memberid = $1

          } */

        //Get when they joined URY
        $events[] = array(
            'timestamp' => strtotime($this->joined),
            'message' => 'joined ' . Config::$short_name,
            'photo' => Config::$photo_joined
        );

        return $events;
    }

    /**
     *
     * @param String $paramName The key to update, e.g. account_locked.
     *                          Don't be silly and try to set memberid. Bad things will happen.
     * @param mixed  $value     The value to set the param to. Type depends on $paramName.
     */
    private function setCommonParam($paramName, $value)
    {
        /**
         * You won't believe how annoying psql can be about '' already being used on a unique key.
	 * You also won't believe that in php, '' == false evaluates to true, so we need ===,
	 *   otherwise a query to change $value to false will not work as desired. 
         */
        if ($value === '') {
            $value = null;
        }
        //Maps Class variable names to their database values, if they mismatch.
        $param_maps = ['collegeid' => 'college'];

        if (!property_exists($this, $paramName)) {
            throw new MyRadioException('paramName invalid', 500);
        }

        if ($this->$paramName == $value) {
            return false;
        }

        $this->$paramName = $value;

        if (isset($param_maps[$paramName])) {
            $paramName = $param_maps[$paramName];
        }

        self::$db->query('UPDATE member SET ' . $paramName . '=$1 WHERE memberid=$2', array($value, $this->getID()));
        $this->updateCacheObject();

        return true;
    }

    /**
     * Sets the User's account locked status.
     *
     * If a User's account is locked, access to all URY services is blocked by
     * MyRadio and IMAP.
     *
     * @param  bool         $bool True for Locked, False for Unlocked. Default True.
     * @return MyRadio_User
     */
    public function setAccountLocked($bool = true)
    {
        $this->setCommonParam('account_locked', $bool);
        return $this;
    }

    /**
     * Sets the user's require password change status.
     * If a user has requested a new password, this should be set to true.
     * Should be set to false when the user actually changes their password.
     *
     * @param bool $bool True for change required, False otherwise. Default T.
     * @return MyRadio_User
     */
    public function setRequirePasswordChange($bool = true)
    {
        $this->setCommonParam('require_password_change', $bool);
        return $this;
    }

    /**
     * Set's a User's college ID.
     *
     * College IDs can be acquired using User::getColleges().
     *
     * @param  int          $college_id The ID of the college.
     * @return MyRadio_User
     */
    public function setCollegeID($college_id)
    {
        $this->setCommonParam('collegeid', $college_id);

        return $this;
    }

    /**
     * Set the user's eduroam address
     *
     * @param  type         $eduroam The User's UoY address, i.e. abc123@york.ac.uk (@york.ac.uk optional)
     * @return MyRadio_User
     */
    public function setEduroam($eduroam)
    {
        //Require the user to be part of this eduroam domain
        if (strstr($eduroam, '@') !== false
            && strstr($eduroam, '@'.Config::$eduroam_domain) === false) {
            throw new MyRadioException(
                'Eduroam account should be @'
                .Config::$eduroam_domain
                .'! Use of other eduroam accounts is blocked.
                This is a basic validation filter, so if there is a valid reason for another account to be here, this check
                can be removed.',
                400
            );
        }

        //Remove the domain if it is set
        $eduroam = str_replace('@'.Config::$eduroam_domain, '', $eduroam);

        if (empty($eduroam) && empty($this->email)) {
            throw new MyRadioException('Can\'t set both Email and Eduroam to null.', 400);
        } elseif ($this->getEduroam() !== $eduroam && MyRadio_User::findByEmail($eduroam) !== null) {
            throw new MyRadioException('The eduroam account ' . $eduroam . ' is already allocated to another User.', 500);
        }
        $this->setCommonParam('eduroam', $eduroam);

        return $this;
    }

    /**
     * Sets the User's primary contact Email. If null, eduroam is used.
     * @param  String           $email
     * @return MyRadio_User
     * @throws MyRadioException
     */
    public function setEmail($email)
    {
        if ($email === '') {
            $email = null;
        }
        if (!empty($email) && strstr($email, '@') === false) {
            throw new MyRadioException('That email address doesn\'t look right. It needs to have an @.', 400);
        }

        if (empty($email) && empty($this->eduroam)) {
            throw new MyRadioException('Can\'t set both Email and Eduroam to null.', 400);
        } elseif ($email !== $this->email && MyRadio_User::findByEmail($email) !== null) {
            throw new MyRadioException('The email account ' . $email . ' is already allocated to another User.', 500);
        }
        $this->setCommonParam('email', $email);

        return $this;
    }

    /**
     * Sets the User's first name
     * @param  String           $fname
     * @return MyRadio_User
     * @throws MyRadioException
     */
    public function setFName($fname)
    {
        if (empty($fname)) {
            throw new MyRadioException('Oh come on, everybody has a name.', 400);
        }
        $this->setCommonParam('fname', $fname);

        return $this;
    }

    /**
     * Set the User's official @ury.org.uk prefix. Usually fname.sname
     * @param  String           $alias
     * @return MyRadio_User
     * @throws MyRadioException
     */
    public function setLocalAlias($alias)
    {
        if ($alias !== $this->local_alias && self::findByEmail($alias) !== null) {
            throw new MyRadioException('That Mailbox Name is already in use. Please choose another.', 500);
        }
        $this->setCommonParam('local_alias', $alias);

        return $this;
    }

    /**
     * Set the User's server account name
     * @param  String           $name
     * @return MyRadio_User
     * @throws MyRadioException
     */
    public function setLocalName($name)
    {
        if ($name !== $this->local_name && self::findByEmail($name) !== null) {
            throw new MyRadioException('That Mailbox Alias is already in use. Please choose another.', 500);
        }
        $this->setCommonParam('local_name', $name);

        return $this;
    }

    /**
     * Set the User's phone number
     * @param  String           $phone A string of numbers (because leading 0)
     * @return MyRadio_User
     * @throws MyRadioException
     */
    public function setPhone($phone)
    {
        //Clear whitespace
        $phone = preg_replace('/\s/', '', $phone);
        if (!empty($phone) && strlen($phone) !== 11) {
            throw new MyRadioException('A phone number should have 11 digits.', 400);
        }
        $this->setCommonParam('phone', $phone);

        return $this;
    }

    /**
     * Set the User's profile photo
     * @param  MyRadio_Photo $photo
     * @return MyRadio_User
     */
    public function setProfilePhoto(MyRadio_Photo $photo)
    {
        $this->setCommonParam('profile_photo', $photo->getID());

        return $this;
    }

    /**
     * Set whether the User should receive Emails
     * @param  boolean      $bool
     * @return MyRadio_User
     */
    public function setReceiveEmail($bool = true)
    {
        $this->setCommonParam('receive_email', $bool);

        return $this;
    }

    /**
     * Set the User's preferred Auth provider
     * @param  String       $provider
     * @return MyRadio_User
     */
    public function setAuthProvider($provider = null)
    {
        $this->setCommonParam('auth_provider', $provider);

        return $this;
    }

    /**
     * Set the User's last name.
     * @param  String           $sname
     * @return MyRadio_User
     * @throws MyRadioException
     */
    public function setSName($sname)
    {
        if (empty($sname)) {
            throw new MyRadioException('Yes, your last name is a thing.', 400);
        }
        $this->setCommonParam('sname', $sname);

        return $this;
    }

    /**
     * Set the User's Gender
     * @param  char             $initial (m)ale, (f)emale or (o)ther
     * @return MyRadio_User
     * @throws MyRadioException
     */
    public function setSex($initial = 'o')
    {
        $initial = strtolower($initial);
        if (!in_array($initial, array('m', 'f', 'o'))) {
            throw new MyRadioException(
                'You can be either "(M)ale", "(F)emale", or "(O)ther". You can\'t be none of these,'
                . ' or more than one of these. Sorry.'
            );
        }
        $this->setCommonParam('sex', $initial);

        return $this;
    }

    /**
     * Set the User's HTML biography.
     * @param  String       $bio
     * @return MyRadio_User
     */
    public function setBio($bio)
    {
        $this->setCommonParam('bio', $bio);

        return $this;
    }

    public function setPayment($amount, $year = null)
    {
        if ($year === null) {
            $year = CoreUtils::getAcademicYear();
        }

        $amount = number_format($amount, 2);

        foreach ($this->payment as $k => $v) {
            if ($v['year'] == $year && $v['paid'] == $amount) {
                return;
            } elseif ($v['year'] == $year) {
                //Change payment.
                self::$db->query(
                    'UPDATE member_year SET paid=$1
                    WHERE year=$2 AND memberid=$3',
                    [(float) $amount, $year, $this->getID()]
                );
                $this->payment[$k]['paid'] = $amount;
                $this->updateCacheObject();

                return;
            }
        }

        //Not a member this year
        self::$db->query(
            'INSERT INTO member_year (paid, year, memberid)
            VALUES ($1, $2, $3)',
            [(float) $amount, $year, $this->getID()]
        );
        $this->payment[] = ['year' => $year, 'amount' => (float) $amount];
        $this->updateCacheObject();

        return;
    }

    /**
     * Sets the User's last login time to right now.
     * Use this when they're being logged in (weird that)
     */
    public function updateLastLogin()
    {
        $this->last_login = CoreUtils::getTimestamp();
        self::$db->query(
            'UPDATE public.member SET last_login=$1
            WHERE memberid=$2',
            [$this->last_login, $this->getID()]
        );
        $this->updateCacheObject();
    }

    /**
     * Searched for the user with the given email address, returning the User if they exist, or null if it fails.
     * @param  String            $email
     * @return null|MyRadio_User
     */
    public static function findByEmail($email)
    {
        if (empty($email)) {
            return null;
        }
        //Doing this instead of ILIKE halves the query time
        $email = strtolower($email);
        self::wakeup();
        $result = self::$db->fetch_column(
            'SELECT memberid FROM public.member WHERE email LIKE $1 OR eduroam LIKE $1
            OR local_name LIKE $2 OR local_alias LIKE $2 OR eduroam LIKE $2',
            array($email, explode('@', $email)[0])
        );

        if (empty($result)) {
            return null;
        } else {
            return self::getInstance($result[0]);
        }
    }

    /**
     * Please use MyRadio_TrainingStatus.
     *
     * @deprecated
     * @return MyRadio_User[]
     */
    public static function findAllTrained()
    {
        self::wakeup();
        trigger_error('Use of deprecated method User::findAllTrained.', E_USER_WARNING);

        $trained = self::$db->fetch_column('SELECT memberid FROM public.member_presenterstatus WHERE presenterstatusid=1');
        $members = array();
        foreach ($trained as $mid) {
            $member = MyRadio_User::getInstance($mid);
            if ($member->isStudioTrained()) {
                $members[] = $member;
            }
        }

        return $members;
    }

    /**
     * Please use MyRadio_TrainingStatus.
     *
     * @deprecated
     * @return MyRadio_User[]
     */
    public static function findAllDemoed()
    {
        self::wakeup();
        trigger_error('Use of deprecated method User::findAllDemoed.', E_USER_WARNING);

        $trained = self::$db->fetch_column('SELECT memberid FROM public.member_presenterstatus WHERE presenterstatusid=2');
        $members = array();
        foreach ($trained as $mid) {
            $member = MyRadio_User::getInstance($mid);
            if ($member->isStudioDemoed()) {
                $members[] = $member;
            }
        }

        return $members;
    }

    /**
     * Please use MyRadio_TrainingStatus.
     *
     * @deprecated
     * @return MyRadio_User[]
     */
    public static function findAllTrainers()
    {
        self::wakeup();
        trigger_error('Use of deprecated method User::findAllTrainers.', E_USER_WARNING);

        $trained = self::$db->fetch_column('SELECT memberid FROM public.member_presenterstatus WHERE presenterstatusid=3');
        $members = array();
        foreach ($trained as $mid) {
            $member = MyRadio_User::getInstance($mid);
            if ($member->isTrainer()) {
                $members[] = $member;
            }
        }

        return $members;
    }

    /**
     * Returns an Array of all mappings for official aliases to emails go to.
     * @return Array[] [[from, to]]
     */
    public static function getAllAliases()
    {
        $users = self::resultSetToObjArray(self::$db->fetch_column(
            'SELECT memberid FROM public.member WHERE local_alias IS NOT NULL'
        ));

        $data = [];
        foreach ($users as $user) {
            $email = $user->getEmail();
            if (empty($email)) {
                continue;
            } else {
                $data[] = [$user->getLocalAlias(), $email];
            }
        }

        return $data;
    }

    /**
     * Gets the edit form for this User, with the permissions available for the current User
     */
    public function getEditForm()
    {
        if ($this->getID() !== MyRadio_User::getInstance()->getID() && !MyRadio_User::getInstance()->hasAuth(AUTH_EDITANYPROFILE)) {
            throw new MyRadioException(MyRadio_User::getInstance() . ' tried to edit ' . $this . '!');
        }

        $form = new MyRadioForm('profileedit', 'Profile', 'doEdit', array('title' => 'Edit Profile'));
        //Personal details
        $form->addField(new MyRadioFormField('memberid', MyRadioFormField::TYPE_HIDDEN, ['value' => $this->getID()]))
                ->addField(new MyRadioFormField('sec_personal', MyRadioFormField::TYPE_SECTION, array(
                    'label' => 'Personal Details'
                )))
                ->addField(new MyRadioFormField('fname', MyRadioFormField::TYPE_TEXT, array(
                    'required' => true,
                    'label' => 'First Name',
                    'value' => $this->getFName()
                )))
                ->addField(new MyRadioFormField('sname', MyRadioFormField::TYPE_TEXT, array(
                    'required' => true,
                    'label' => 'Last Name',
                    'value' => $this->getSName()
                )))
                ->addField(new MyRadioFormField('sex', MyRadioFormField::TYPE_SELECT, array(
                    'required' => true,
                    'label' => 'Gender',
                    'value' => $this->getSex(),
                    'options' => array(
                        array('value' => 'm', 'text' => 'Male'),
                        array('value' => 'f', 'text' => 'Female'),
                        array('value' => 'o', 'text' => 'Other')
                    )
                )))
                ->addField(new MyRadioFormField('sec_personal_close', MyRadioFormField::TYPE_SECTION_CLOSE));

        //Contact details
        $form->addField(new MyRadioFormField('sec_contact', MyRadioFormField::TYPE_SECTION, array(
                    'label' => 'Contact Details'
                )))
                ->addField(new MyRadioFormField('collegeid', MyRadioFormField::TYPE_SELECT, array(
                    'required' => true,
                    'label' => 'College',
                    'options' => self::getColleges(),
                    'value' => $this->getCollegeID()
                )))
                ->addField(new MyRadioFormField('phone', MyRadioFormField::TYPE_TEXT, array(
                    'required' => false,
                    'label' => 'Phone Number',
                    'value' => $this->getPhone()
                )))
                ->addField(new MyRadioFormField('email', MyRadioFormField::TYPE_EMAIL, array(
                    'required' => false,
                    'label' => 'Email',
                    'value' => $this->email
                )))
                ->addField(new MyRadioFormField('receive_email', MyRadioFormField::TYPE_CHECK, array(
                    'required' => false,
                    'label' => 'Receive Email?',
                    'options' => array('checked' => $this->getReceiveEmail()),
                    'explanation' => 'If unchecked, you will receive no emails, even if you are subscribed to mailing lists.'
                )))
                ->addField(new MyRadioFormField('eduroam', MyRadioFormField::TYPE_TEXT, array(
                    'required' => false,
                    'label' => 'University Email',
                    'value' => str_replace('@york.ac.uk', '', $this->getUniAccount()),
                    'explanation' => '@york.ac.uk'
                )))
                ->addField(new MyRadioFormField('sec_contact_close', MyRadioFormField::TYPE_SECTION_CLOSE));

        //About Me
        $form->addField(new MyRadioFormField(
            'sec_about',
            MyRadioFormField::TYPE_SECTION,
            array(
                'label' => 'About Me',
                'explanation' => 'If you\'d like to share a little more about yourself, then I\'m happy to listen!'
            )
        ))->addField(new MyRadioFormField(
            'photo',
            MyRadioFormField::TYPE_FILE,
            array(
                'required' => false,
                'label' => 'Profile Photo',
                'explanation' => 'Share your Radio Face with all our members. If we ever launch presenter pages on the website, we\'ll use this there too.'
            )
        ))->addField(new MyRadioFormField(
            'bio',
            MyRadioFormField::TYPE_BLOCKTEXT,
            array(
                'required' => false,
                'label' => 'Bio',
                'explanation' => 'Tell use about yourself - if you\'re a committee member please introduce yourself!',
                'value' => $this->getBio()
            )
        ))->addField(new MyRadioFormField('sec_about_close', MyRadioFormField::TYPE_SECTION_CLOSE));

        //Mailbox
        if (MyRadio_User::getInstance()->hasAuth(AUTH_CHANGESERVERACCOUNT)) {
            $form->addField(new MyRadioFormField('sec_server', MyRadioFormField::TYPE_SECTION, array(
                        'label' => Config::$short_name . ' Mailbox Account',
                        'explanation' => 'Before changing these settings, please ensure you understand the guidelines and'
                            . ' documentation on ' . Config::$long_name . '\'s Internal Email Service'
                    )))
                    ->addField(new MyRadioFormField('local_name', MyRadioFormField::TYPE_TEXT, array(
                        'required' => false,
                        'label' => 'Server Account (Mailbox)',
                        'value' => $this->getLocalName(),
                        'explanation' => 'Best practice is their ITS Username'
                    )))
                    ->addField(new MyRadioFormField('local_alias', MyRadioFormField::TYPE_TEXT, array(
                        'required' => false,
                        'label' => '@ury.org.uk Alias',
                        'value' => $this->getLocalAlias(),
                        'explanation' => 'Usually, this is firstname.lastname (i.e. ' .
                        strtolower($this->getFName() . '.' . $this->getSName()) . ')'
                    )))
                    ->addField(new MyRadioFormField('sec_server_close', MyRadioFormField::TYPE_SECTION_CLOSE));
        }

        return $form;
    }

    public static function getColleges()
    {
        return self::$db->fetch_all('SELECT collegeid AS value, descr AS text FROM public.l_college');
    }

    /**
     * Create a new User, returning the user. At least one of Email OR eduroam
     * must be filled in. Password will be generated automatically and emailed to
     * the user.
     *
     * @param  string           $fname         The User's first name.
     * @param  string           $sname         The User's last name.
     * @param  string           $eduroam       The User's @york.ac.uk address.
     * @param  char             $sex           The User's gender.
     * @param  int              $collegeid     The User's college.
     * @param  string           $email         The User's non @york.ac.uk address.
     * @param  string           $phone         The User's phone number.
     * @param  bool             $receive_email Whether the User should receive emails.
     * @param  float            $paid          How much the User has paid this Membership Year
     * @return MyRadio_User
     * @throws MyRadioException
     */
    public static function create($fname, $sname, $eduroam = null, $sex = 'o', $collegeid = null, $email = null, $phone = null, $receive_email = true, $paid = 0.00)
    {
        /**
         * Deal with the UNIQUE constraint on the DB table.
         */
        if ($phone === '') {
            $phone = null;
        }
        //Validate input
        if (empty($collegeid)) {
            $collegeid = Config::$default_college;
        } elseif (!is_numeric($collegeid)) {
            throw new MyRadioException('Invalid College ID!', 400);
        }

        if (empty($eduroam) && empty($email)) {
            throw new MyRadioException('At least one of eduroam or email must be provided.', 400);
        }

        //Require the user to be part of this eduroam domain
        if (strstr($eduroam, '@') !== false
            && strstr($eduroam, '@'.Config::$eduroam_domain) === false
        ) {
            throw new MyRadioException(
                'Eduroam account should be @'.Config::$eduroam_domain.'! Use of other eduroam accounts is blocked.
                This is a basic validation filter, so if there is a valid reason for another account to be here, this check
                can be removed.',
                400
            );
        }

        //Remove the domain if it is set
        $eduroam = str_replace('@'.Config::$eduroam_domain, '', $eduroam);

        if (empty($eduroam) && empty($this->email)) {
            throw new MyRadioException('Can\'t set both Email and Eduroam to null.', 400);
        }

        if ($sex !== 'm' && $sex !== 'f' && $sex !== 'o') {
            throw new MyRadioException('User gender must be m, f or o!', 400);
        }

        if (!is_numeric($paid)) {
            throw new MyRadioException('Invalid payment amount!', 400);
        }

        //Check if it looks like the user might already exist
        if (MyRadio_User::findByEmail($eduroam) !== null
            or MyRadio_User::findByEmail($email) !== null
        ) {
            throw new MyRadioException(
                'This User already appears to exist. '
                . 'Their eduroam or email is already used.'
            );
        }

        //Looks good. Generate a password for them.
        $plain_pass = CoreUtils::newPassword();

        //Actually create the member!
        $r = self::$db->fetch_column(
            'INSERT INTO public.member (fname, sname, sex,
            college, phone, email, receive_email, eduroam, require_password_change)
            VALUES ($1, $2, $3, $4, $5, $6, $7, $8, $9) RETURNING memberid',
            array(
                $fname,
                $sname,
                $sex,
                $collegeid,
                $email,
                $phone,
                $receive_email,
                $eduroam,
                true
            )
        );

        if (empty($r)) {
            throw new MyRadioException('Failed to create User!', 500);
        }

        $memberid = $r[0];
        $user = MyRadio_User::getInstance($memberid);

        //Activate the member's account for the current academic year
        $user->activateMemberThisYear($paid);
        //Set the user's password
        Shibbobleh_Utils::setPassword($memberid, $plain_pass);

        //Send a welcome email (this will not send if receive_email is not enabled!)
        /**
         * @todo Make this easier to change
         * @todo Link to Facebook events
         */
        $uname = empty($eduroam) ? $email : str_replace('@york.ac.uk', '', $eduroam);
        $welcome_email = str_replace(['#NAME', '#USER', '#PASS'], [$fname, $uname, $plain_pass], Config::$welcome_email);

        //Send the email
        MyRadioEmail::create(array('members' => array(MyRadio_User::getInstance($memberid))), 'Welcome to ' . Config::$short_name . ' - Getting Involved and Your Account', $welcome_email, 'getinvolved@' . Config::$email_domain);

        return MyRadio_User::getInstance($memberid);
    }

    /**
     * Update a User's account so that they are active for the current academic year.
     *
     * Activating a membership re-activates basic access to web services, and
     * renews their mailing list subscriptions.
     *
     * @param  int     $paid
     * @return boolean
     */
    public function activateMemberThisYear($paid = 0)
    {
        self::$db->query('INSERT INTO public.member_year (memberid, year, paid) VALUES ($1, $2, $3)', array($this->getID(), CoreUtils::getAcademicYear(), $paid));

        return true;
    }

    /**
     * Generates the form needed to quick-add URY members
     * @throws MyRadioException
     * @return MyRadioForm
     */
    public static function getQuickAddForm()
    {
        if (!MyRadio_User::getInstance()->hasAuth(AUTH_ADDMEMBER)) {
            throw new MyRadioException(MyRadio_User::getInstance() . ' tried to add members!');
        }

        $form = new MyRadioForm('profilequickadd', 'Profile', 'doQuickAdd', array('title' => 'Add Member (Quick)'));
        //Personal details
        $form->addField(new MyRadioFormField('sec_personal', MyRadioFormField::TYPE_SECTION, array(
                    'label' => 'Personal Details'
                )))
                ->addField(new MyRadioFormField('fname', MyRadioFormField::TYPE_TEXT, array(
                    'required' => true,
                    'label' => 'First Name'
                )))
                ->addField(new MyRadioFormField('sname', MyRadioFormField::TYPE_TEXT, array(
                    'required' => true,
                    'label' => 'Last Name'
                )))
                ->addField(new MyRadioFormField('sex', MyRadioFormField::TYPE_SELECT, array(
                    'required' => true,
                    'label' => 'Gender',
                    'options' => array(
                        array('value' => 'm', 'text' => 'Male'),
                        array('value' => 'f', 'text' => 'Female'),
                        array('value' => 'o', 'text' => 'Other')
                    )
        )));

        //Contact details
        $form->addField(new MyRadioFormField('sec_contact', MyRadioFormField::TYPE_SECTION, array(
                    'label' => 'Contact Details'
                )))
                ->addField(new MyRadioFormField('collegeid', MyRadioFormField::TYPE_SELECT, array(
                    'required' => true,
                    'label' => 'College',
                    'options' => self::getColleges()
                )))
                ->addField(new MyRadioFormField('eduroam', MyRadioFormField::TYPE_TEXT, array(
                    'required' => true,
                    'label' => 'University Email',
                    'explanation' => '@york.ac.uk'
                )))
                ->addField(new MyRadioFormField('phone', MyRadioFormField::TYPE_TEXT, array(
                    'required' => false,
                    'label' => 'Phone Number'
        )));

        return $form;
    }

    /**
     * Generates the form needed to bulk-add URY members
     * @throws MyRadioException
     * @return MyRadioForm
     */
    public static function getBulkAddForm()
    {
        if (!MyRadio_User::getInstance()->hasAuth(AUTH_ADDMEMBER)) {
            throw new MyRadioException(MyRadio_User::getInstance() . ' tried to add members!');
        }

        $form = new MyRadioForm('profilebulkadd', 'Profile', 'doBulkAdd', array('title' => 'Add Member (Bulk)'));
        //Personal details
        $form->addField(new MyRadioFormField('bulkaddrepeater', MyRadioFormField::TYPE_TABULARSET, array(
            'options' => array(
                new MyRadioFormField('fname', MyRadioFormField::TYPE_TEXT, array(
                    'required' => true,
                    'label' => 'First Name'
                        )),
                new MyRadioFormField('sname', MyRadioFormField::TYPE_TEXT, array(
                    'required' => true,
                    'label' => 'Last Name'
                        )),
                new MyRadioFormField('sex', MyRadioFormField::TYPE_SELECT, array(
                    'required' => true,
                    'label' => 'Gender',
                    'options' => array(
                        array('value' => 'm', 'text' => 'Male'),
                        array('value' => 'f', 'text' => 'Female'),
                        array('value' => 'o', 'text' => 'Other')
                    ))),
                new MyRadioFormField('collegeid', MyRadioFormField::TYPE_SELECT, array(
                    'required' => true,
                    'label' => 'College',
                    'options' => self::getColleges()
                        )),
                new MyRadioFormField('eduroam', MyRadioFormField::TYPE_TEXT, array(
                    'required' => true,
                    'label' => 'University Email',
                    'explanation' => '@york.ac.uk'
                        ))
            )
        )));

        return $form;
    }

    public function toDataSource($full = true)
    {
        $data = [
            'memberid' => $this->getID(),
            'locked' => $this->getAccountLocked(),
            'college' => $this->getCollege(),
            'fname' => $this->getFName(),
            'sname' => $this->getSName(),
            'sex' => $this->getSex(),
            'receive_email' => $this->getReceiveEmail(),
            'public_email' => $this->getEmail(),
            'url' => $this->getURL(),
            'local_name' => $this->getLocalName()
        ];
        if ($full) {
            $data['paid'] = $this->getAllPayments();
            $data['photo'] = $this->getProfilePhoto() === null ?
                Config::$default_person_uri : $this->getProfilePhoto()->getURL();
            $data['bio'] = $this->getBio();
            $data['shows'] = CoreUtils::dataSourceParser($this->getShows(), false);
            $data['officerships'] = $this->getOfficerships();
            $data['training'] = CoreUtils::dataSourceParser($this->getAllTraining(), false);
        }

        return $data;
    }
}
