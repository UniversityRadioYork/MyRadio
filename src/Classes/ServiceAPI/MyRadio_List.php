<?php
/**
 * This file provides the List class for MyRadio.
 */
namespace MyRadio\ServiceAPI;

use MyRadio\MyRadioException;
use MyRadio\MyRadio\CoreUtils;
use MyRadio\MyRadio\URLUtils;
use MyRadio\MyRadioEmail;

/**
 * The List class stores and manages information about a URY Mailing List.
 *
 * @uses    \Database
 */
class MyRadio_List extends ServiceAPI
{
    /**
     * Stores the primary key for the list.
     *
     * @var int
     */
    private $listid;

    /**
     * Stores the user-friendly name of the list.
     *
     * @var string
     */
    private $name;

    public static function getGraphQLTypeName()
    {
        return 'MailingList';
    }

    /**
     * If non-optin, stores the SQL query that returns the member memberids.
     *
     * @var string
     */
    private $sql;

    /**
     * If true, this mailing list has an @ury.org.uk alias that is publically usable.
     *
     * @var bool
     */
    private $public;

    /**
     * If public, this is the prefix for the email address (i.e. "cactus")
     * would be cactus@ury.org.uk.
     *
     * @var string
     */
    private $address;

    /**
     * If true, this means that members subscribe themselves to this list.
     *
     * @var bool
     */
    private $optin;

    /**
     * This is the set of members that receive messages to this list.
     *
     * @var int[]
     */
    private $members = [];

    /**
     * Initialised on first request, stores an archive of all the email IDs
     * sent to this list.
     *
     * @var int[]
     */
    private $archive = [];

    /**
     * Initiates the MyRadio_List object.
     *
     * @param $listid The ID of the Mailing List to initialise
     */
    protected function __construct($listid)
    {
        $this->listid = (int) $listid;

        $result = self::$db->fetchOne('SELECT * FROM mail_list WHERE listid=$1', [$this->listid]);
        if (empty($result)) {
            throw new MyRadioException('List '.$listid.' does not exist!');

            return;
        }

        $this->name = $result['listname'];
        $this->sql = $result['defn'];
        $this->public = $result['toexim'];
        $this->address = $result['listaddress'];
        $this->optin = $result['subscribable'] === 't';

        if ($this->optin) {
            //Get subscribed members
            $this->members = self::$db->fetchColumn(
                'SELECT memberid FROM mail_subscription WHERE listid=$1',
                [$listid]
            );
        } else {
            //Get members joined with opted-out members
            $this->members = self::$db->fetchColumn(
                'SELECT memberid FROM ('.$this->parseSQL($this->sql).') as t1 WHERE memberid NOT IN
                (SELECT memberid FROM mail_subscription WHERE listid=$1)',
                [$listid]
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
            ['%LISTID', '%Y', '%BOY'],
            [
                $this->getID(),
                CoreUtils::getAcademicYear(),
                '\''.CoreUtils::getAcademicYear().'-10-01 00:00:00\'',
            ],
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

    public function isMember($userid)
    {
        return in_array($userid, $this->members);
    }

    /**
     * Returns if the user has permission to email this list.
     *
     * @param MyRadio_User $user
     *
     * @return bool
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
     * Returns false if they are still a member of the list, or if this is subscribable.
     *
     * @param int $userid
     */
    public function hasOptedOutOfAuto($userid)
    {
        if ($this->optin) {
            return false;
        }

        return sizeof(
            self::$db->fetchColumn(
                'SELECT memberid FROM public.mail_subscription WHERE memberid=$1 AND listid=$2',
                [$userid, $this->getID()]
            )
        ) === 1;
    }

    /**
     * If the mailing list is subscribable, opt the user in if they aren't already.
     * If the mailing list is automatic, but the user has previously opted out, remove this opt-out entry.
     *
     * @param int $userid
     *
     * @return bool True if the user is now opted in, false if they could not be opted in.
     *
     * @todo Auto-rebuild Exim routing after change
     */
    public function optin($userid)
    {
        //User is already opted in
        if ($this->isMember($userid)) {
            return true;
        }

        if (!$this->optin && !$this->hasOptedOutOfAuto($userid)) {
            return false;
        }

        if ($this->optin) {
            self::$db->query(
                'INSERT INTO public.mail_subscription (memberid, listid) VALUES ($1, $2)',
                [$userid, $this->getID()]
            );
        } else {
            self::$db->query(
                'DELETE FROM public.mail_subscription WHERE memberid=$1 AND listid=$2',
                [$userid, $this->getID()]
            );
        }

        $this->members[] = $userid;
        $this->updateCacheObject();

        return true;
    }

    /**
     * If the mailing list is subscribable, opt the user out if they are currently subscribed.
     * If the mailing list is automatic, opt-the user out of the list.
     *
     * @param   $userid
     *
     * @return bool True if the user is now opted out, false if they could not be opted out.
     *
     * @todo Auto-rebuild Exim routing after change
     */
    public function optout(int $userid)
    {
        if (!$this->isMember($userid)) {
            return false;
        }

        if (!$this->optin) {
            self::$db->query(
                'INSERT INTO public.mail_subscription (memberid, listid) VALUES ($1, $2)',
                [$userid, $this->getID()]
            );
        } else {
            self::$db->query(
                'DELETE FROM public.mail_subscription WHERE memberid=$1 AND listid=$2',
                [$userid, $this->getID()]
            );
        }

        $key = array_search($userid, $this->members);
        if ($key !== false) {
            unset($this->members[$key]);
        }
        $this->updateCacheObject();

        return true;
    }

    /**
     * Takes an email and puts it in the online Email Archive.
     *
     * @param MyRadio_User $from
     * @param string       $email
     */
    public function archiveMessage($from, $email)
    {
        $body = str_replace("=\r\n", '', preg_split("/\r?\n\r?\n/", utf8_encode($email), 2)[1]);
        preg_match('/(^|\s)Subject:(.*)/i', $email, $subject);
        $subject = trim($subject[2]);

        MyRadioEmail::create(['lists' => [$this]], $subject, $body, $from, time(), true);
        $this->archive = [];
        $this->updateCacheObject();
    }

    /**
     * Return all the emails Archived in this List.
     *
     * @return MyRadioEmail[]
     */
    public function getArchive()
    {
        if (empty($this->archive)) {
            $this->archive = self::$db->fetchColumn(
                'SELECT email.email_id '
                .'FROM mail.email_recipient_list '
                .'LEFT JOIN mail.email USING (email_id) '
                .'WHERE listid=$1 '
                .'ORDER BY timestamp DESC',
                [$this->getID()]
            );
            $this->updateCacheObject();
        }

        return MyRadioEmail::resultSetToObjArray($this->archive);
    }

    /**
     * @param $str
     * @return MyRadio_List|null
     */
    public static function getByName($str)
    {
        self::initDB();
        $r = self::$db->fetchColumn(
            'SELECT listid FROM mail_list WHERE listname ILIKE $1 OR listaddress ILIKE $1',
            [$str]
        );
        if (empty($r)) {
            return null;
        } else {
            return self::getInstance($r[0]);
        }
    }

    /**
     * Return all mailing lists.
     *
     * @param bool $hideExcluded if true, will exclude lists with a negative ordering
     * @return MyRadio_List[]
     */
    public static function getAllLists($hideExcluded=false)
    {
        $r = self::$db->fetchColumn('SELECT listid FROM mail_list '
            . ($hideExcluded ? 'WHERE ordering >= 0' : '')
            .' ORDER BY ordering, listid');

        $lists = [];
        foreach ($r as $list) {
            $lists[] = self::getInstance($list);
        }

        return $lists;
    }

    /**
     * Returns data about the List.
     *
     * @mixin actions Returns interaction options for the UI
     * @mixin recipients Lists recipients of the list
     *
     * @return array
     */
    public function toDataSource($mixins = [])
    {
        $mixin_funcs = [
            'actions' => function (&$data) {
                if (!$data['subscribed']
                    && ($this->optin || $this->hasOptedOutOfAuto(MyRadio_User::getCurrentOrSystemUser()->getID()))
                ) {
                    $data['optin'] = [
                        'display' => 'icon',
                        'value' => 'plus',
                        'title' => 'Subscribe to this mailing list',
                        'url' => URLUtils::makeURL('Mail', 'optin', ['list' => $this->getID()]),
                    ];
                } else {
                    $data['optin'] = null;
                }
                $data['optOut'] = ($data['subscribed'] ? [
                    'display' => 'icon',
                    'value' => 'minus',
                    'title' => 'Opt out of this mailing list',
                    'url' => URLUtils::makeURL('Mail', 'optout', ['list' => $this->getID()]),
                ] : null);
                $data['mail'] = [
                    'display' => 'icon',
                    'value' => 'envelope',
                    'title' => 'Send a message to this mailing list',
                    'url' => URLUtils::makeURL('Mail', 'send', ['list' => $this->getID()]),
                ];
                $data['archive'] = [
                    'display' => 'icon',
                    'value' => 'folder-close',
                    'title' => 'View archives for this mailing list',
                    'url' => URLUtils::makeURL('Mail', 'archive', ['list' => $this->getID()]),
                ];
            },
            'recipients' => function (&$data) {
                $data['recipients'] = CoreUtils::dataSourceParser($this->getMembers());
            },
        ];

        if (isset($_SESSION['memberid'])) {
            $subscribed = $this->isMember(MyRadio_User::getInstance()->getID());
        } else {
            $subscribed = false;
        }

        $data = [
            'listid' => $this->getID(),
            'subscribed' => $subscribed,
            'name' => $this->getName(),
            'address' => $this->getAddress(),
            'recipient_count' => sizeof($this->members),
        ];

        $this->addMixins($data, $mixins, $mixin_funcs);

        return $data;
    }
}
