<?php
/**
 * This file provides the List class for MyRadio
 * @package MyRadio_Core
 */

/**
 * The List class stores and manages information about a URY Mailing List
 *
 * @version 20130526
 * @author Lloyd Wallis <lpw@ury.org.uk>
 * @package MyRadio_Mail
 * @uses \Database
 */
class MyRadio_List extends ServiceAPI
{
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
     * If public, this is the prefix for the email address (i.e. "cactus")
     * would be cactus@ury.org.uk
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
     * @var int[]
     */
    private $members = array();

    /**
     * Initialised on first request, stores an archive of all the email IDs
     * sent to this list.
     * @var int[]
     */
    private $archive = [];

    /**
     * Initiates the MyRadio_List object
     * @param int $listid The ID of the Mailing List to initialise
     */
    protected function __construct($listid)
    {
        $this->listid = $listid;

        $result = self::$db->fetch_one('SELECT * FROM mail_list WHERE listid=$1', array($listid));
        if (empty($result)) {
            throw new MyRadioException('List ' . $listid . ' does not exist!');

            return null;
        }

        $this->name = $result['listname'];
        $this->sql = $result['defn'];
        $this->public = $result['toexim'];
        $this->address = $result['listaddress'];
        $this->optin = $result['subscribable'] === 't';

        if ($this->optin) {
            //Get subscribed members
            $this->members = self::$db->fetch_column('SELECT memberid FROM mail_subscription WHERE listid=$1', array($listid));
        } else {
            //Get members joined with opted-out members
            $this->members = self::$db->fetch_column(
                'SELECT memberid FROM (' . $this->parseSQL($this->sql) . ') as t1 WHERE memberid NOT IN
                (SELECT memberid FROM mail_subscription WHERE listid=$1)',
                array($listid)
            );
        }
        $this->members = array_map(
            function ($x) {
                return (int) $x;
            },
            $this->members
        );
    }

    private function parseSQL($sql)
    {
        $sql = str_replace(
            array('%LISTID', '%Y', '%BOY'),
            array(
                $this->getID(),
                CoreUtils::getAcademicYear(),
                '\'' . CoreUtils::getAcademicYear() . '-10-01 00:00:00\''
            ),
            $sql
        );

        return $sql;
    }

    public function getMembers()
    {
        return MyRadio_User::resultSetToObjArray($this->members);
    }

    public function getID()
    {
        return $this->listid;
    }

    public function getName()
    {
        return $this->name;
    }

    public function getAddress()
    {
        return $this->address;
    }

    public function isPublic()
    {
         return $this->public;
    }

    public function isMember(MyRadio_User $user)
    {
        return in_array($user->getID(), $this->members);
    }

    /**
     * Returns if the user has permission to email this list
     * @param  MyRadio_User $user
     * @return boolean
     */
    public function hasSendPermission(MyRadio_User $user)
    {
        if (!$this->public && !$user->hasAuth(AUTH_MAILALLMEMBERS)) {
            return false;
        }

        return true;
    }

    /**
     * Returns true if the user has *actively opted out* of an *automatic* mailing list
     * Returns false if they are still a member of the list, or if this is subscribable
     * @param MyRadio_User $user
     */
    public function hasOptedOutOfAuto(MyRadio_User $user)
    {
        if ($this->optin) {
            return false;
        }

        return sizeof(self::$db->query(
            'SELECT memberid FROM public.mail_subscription WHERE memberid=$1 AND listid=$2',
            array($user->getID(), $this->getID())
        )) === 1;
    }

    /**
     * If the mailing list is subscribable, opt the user in if they aren't already.
     * If the mailing list is automatic, but the user has previously opted out, remove this opt-out entry.
     * @param  MyRadio_User $user
     * @return boolean      True if the user is now opted in, false if they could not be opted in.
     * @todo Auto-rebuild Exim routing after change
     */
    public function optin(MyRadio_User $user)
    {
        if ($this->isMember($user)) {
            return false;
        }

        if (!$this->optin && !$this->hasOptedOutOfAuto($user)) {
            return false;
        }

        //User is already opted in
        if (in_array($user, $this->getMembers())) {
            return true;
        }

        if ($this->optin) {
            self::$db->query(
                'INSERT INTO public.mail_subscription (memberid, listid) VALUES ($1, $2)',
                array($user->getID(), $this->getID())
            );
        } else {
            self::$db->query(
                'DELETE FROM public.mail_subscription WHERE memberid=$1 AND listid=$2',
                array($user->getID(), $this->getID())
            );
        }

        $this->members[] = $user->getID();
        $this->updateCacheObject();

        return true;
    }

    /**
     * If the mailing list is subscribable, opt the user out if they are currently subscribed.
     * If the mailing list is automatic, opt-the user out of the list.
     * @param  MyRadio_User $user
     * @return boolean      True if the user is now opted out, false if they could not be opted out.
     * @todo Auto-rebuild Exim routing after change
     */
    public function optout(MyRadio_User $user)
    {
        if (!$this->isMember($user)) {
            return false;
        }

        if (!$this->optin) {
            self::$db->query(
                'INSERT INTO public.mail_subscription (memberid, listid) VALUES ($1, $2)',
                array($user->getID(), $this->getID())
            );
        } else {
            self::$db->query(
                'DELETE FROM public.mail_subscription WHERE memberid=$1 AND listid=$2',
                array($user->getID(), $this->getID())
            );
        }

        $key = array_search($user->getID(), $this->members);
        if ($key !== false) {
            unset($this->members[$key]);
        }
        $this->updateCacheObject();

        return true;
    }

    /**
     * Takes an email and puts it in the online Email Archive
     *
     * @param MyRadio_User $from
     * @param String       $email
     */
    public function archiveMessage($from, $email)
    {
        $body = str_replace("=\r\n", '', preg_split("/\r?\n\r?\n/", utf8_encode($email), 2)[1]);
        preg_match('/(^|\s)Subject:(.*)/i', $email, $subject);
        $subject = trim($subject[2]);

        MyRadioEmail::create(array('lists' => array($this)), $subject, $body, $from, time(), true);
        $this->archive = [];
        $this->updateCacheObject();
    }

    /**
     * Return all the emails Archived in this List.
     * @return MyRadioEmail[]
     */
    public function getArchive()
    {
        if (empty($this->archive)) {
            $this->archive = self::$db->fetch_column(
                'SELECT email.email_id '
                . 'FROM mail.email_recipient_list '
                . 'LEFT JOIN mail.email USING (email_id) '
                . 'WHERE listid=$1 '
                . 'ORDER BY timestamp DESC',
                [$this->getID()]
            );
            $this->updateCacheObject();
        }

        return MyRadioEmail::resultSetToObjArray($this->archive);
    }

    public static function getByName($str)
    {
        self::initDB();
        $r = self::$db->fetch_column(
            'SELECT listid FROM mail_list WHERE listname ILIKE $1 OR listaddress ILIKE $1',
            array($str)
        );
        if (empty($r)) {
            return null;
        } else {
            return self::getInstance($r[0]);
        }
    }

    public static function getAllLists()
    {
        $r = self::$db->fetch_column('SELECT listid FROM mail_list');

        $lists = array();
        foreach ($r as $list) {
            $lists[] = self::getInstance($list);
        }

        return $lists;
    }

    public function toDataSource($full = true)
    {
        if (isset($_SESSION['memberid'])) {
            $subscribed = $this->isMember(MyRadio_User::getInstance());
        } else {
            $subscribed = false;
        }

        return array(
            'listid' => $this->getID(),
            'subscribed' => $subscribed,
            'name' => $this->getName(),
            'address' => $this->getAddress(),
            'recipient_count' => sizeof($this->getMembers()),
            'optIn' => ((!$subscribed && ($this->optin || $this->hasOptedOutOfAuto(MyRadio_User::getCurrentOrSystemUser()))) ?
                array(
                    'display' => 'icon',
                    'value' => 'circle-plus',
                    'title' => 'Subscribe to this mailing list',
                    'url' => CoreUtils::makeURL('Mail', 'optin', array('list' => $this->getID()))
                ) : null
            ),
            'optOut' => ($subscribed ? array(
                'display' => 'icon',
                'value' => 'circle-minus',
                'title' => 'Opt out of this mailing list',
                'url' => CoreUtils::makeURL('Mail', 'optout', array('list' => $this->getID()))
            ) : null),
            'mail' => array(
                'display' => 'icon',
                'value' => 'mail-closed',
                'title' => 'Send a message to this mailing list',
                'url' => CoreUtils::makeURL('Mail', 'send', array('list' => $this->getID()))
            ),
            'archive' => array(
                'display' => 'icon',
                'value' => 'disk',
                'title' => 'View archives for this mailing list',
                'url' => CoreUtils::makeURL('Mail', 'archive', array('list' => $this->getID()))
            )
        );
    }
}
