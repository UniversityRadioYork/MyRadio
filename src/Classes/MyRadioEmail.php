<?php

/**
 * This file provides the MyRadioError class for MyRadio.
 */
namespace MyRadio;

use MyRadio\MyRadio\CoreUtils;
use MyRadio\MyRadio\URLUtils;
use MyRadio\ServiceAPI\ServiceAPI;
use MyRadio\ServiceAPI\MyRadio_User;
use MyRadio\ServiceAPI\MyRadio_List;

use PHPMailer\PHPMailer\PHPMailer;

/**
 * Provides email functions so that MyRadio can send email.
 */
class MyRadioEmail extends ServiceAPI
{
    /**
     * @var string carriage return + newline
     */
    private static $rtnl = "\r\n";
    private $email_id;
    private $r_lists = [];
    private $r_users = [];
    private $subject;
    private ?MyRadio_User $from;
    private $timestamp;
    private string $body;
    private string $body_alt;

    private PHPMailer $mailer;

    protected function __construct($eid)
    {
        self::$db = Database::getInstance();

        $info = self::$db->fetchOne('SELECT * FROM mail.email WHERE email_id=$1', [$eid]);

        if (empty($info)) {
            throw new MyRadioException('Email '.$eid.' does not exist!');
        }

        $this->subject = $info['subject'];
        $this->from = (empty($info['sender']) ? null : MyRadio_User::getInstance($info['sender']));
        $this->timestamp = strtotime($info['timestamp']);
        $this->email_id = $eid;

        $this->r_users = self::$db->fetchColumn(
            'SELECT memberid FROM mail.email_recipient_member WHERE email_id=$1',
            [$eid]
        );
        $this->r_lists = self::$db->fetchColumn(
            'SELECT listid FROM mail.email_recipient_list WHERE email_id=$1',
            [$eid]
        );

        $this->mailer = new PHPMailer(true);

        if ($this->from !== null) {
            $this->mailer->setFrom($this->from->getEmail(), $this->from->getName());
            $this->mailer->Sender = $this->from->getEmail();
        } else {
            $this->mailer->setFrom('no-reply@'.Config::$email_domain, Config::$long_name);
            $this->mailer->Sender = 'no-reply@'.Config::$email_domain;
        }

        /*
         * Check if the body needs to be split into multipart.
         * This creates a string with both Text and HTML parts.
         */
        $body = $info['body'];
        $split = strip_tags($body);
        if ($body !== $split) {
            //There's HTML in there
            $split = \Html2Text\Html2Text::convert($body, true); // ignore errors
            $this->mailer->isHTML(true);
            $this->body = self::addHTMLFooter($body);
            $this->body_alt = self::addFooter($split);
        } else {
            $this->body = self::addFooter($body);
        }
    }

    /**
     * Create a new email.
     *
     * @param MyRadio_User $from         The User who sent the email. If null, uses no-reply
     * @param array        $to           A 2D array of 'lists' = [l1, l2], 'members' = [m1, m2]
     * @param string       $subject      email subject
     * @param string       $body         email body
     * @param int          $timestamp    Send time. If null, use now.
     * @param bool         $already_sent If true, all Recipients will be set to having had the email sent.
     * @note Use one of the SendToUser* wrapper functions instead of this one.
     */
    private static function create($to, $subject, $body, $from = null, $timestamp = null, $already_sent = false)
    {
        //Remove duplicate recipients
        $to['lists'] = empty($to['lists']) ? [] : array_unique($to['lists']);
        $to['members'] = empty($to['members']) ? [] : array_unique($to['members']);

        if (!is_bool($already_sent)) {
            $already_sent = false;
        }
        self::$db = Database::getInstance();

        if (strlen($body) > 1024000) {
            //Woah - that's a big email. Where's this coming from?
            //If its more than a couple MB expect this script/service to shortly die due to RAM usage.
            $caller = array_shift(debug_backtrace());
            trigger_error(
                'Received long email body: '.strlen($body).' bytes. Source: '
                .$from.'/'.$caller['file'].':'.$caller['line'],
                E_USER_NOTICE
            );
        }

        $params = [$subject, trim($body)];
        if ($timestamp !== null) {
            $params[] = CoreUtils::getTimestamp($timestamp);
        }
        if ($from instanceof ServiceAPI) {
            $params[] = $from->getID();
        }

        $eid = self::$db->fetchColumn(
            'INSERT INTO mail.email (subject, body'
            .($timestamp !== null ? ', timestamp' : '').($from instanceof ServiceAPI ? ', sender' : '').')
            VALUES ($1, $2'.(($timestamp !== null or $from instanceof ServiceAPI) ? ', $3' : '')
            .(($timestamp !== null && $from !== null) ? ', $4' : '').') RETURNING email_id',
            $params
        );

        $eid = $eid[0];

        if (empty($eid)) {
            throw new MyRadioException('Failed to create email. See previous error.');
        }

        if (!empty($to['lists'])) {
            foreach ($to['lists'] as $list) {
                if (is_object($list)) {
                    $list = $list->getID();
                }
                self::$db->query(
                    'INSERT INTO mail.email_recipient_list (email_id, listid, sent) VALUES ($1, $2, $3)',
                    [$eid, $list, $already_sent]
                );
            }
        }
        if (!empty($to['members'])) {
            foreach ($to['members'] as $member) {
                if (is_object($member)) {
                    $member = $member->getID();
                }
                self::$db->query(
                    'INSERT INTO mail.email_recipient_member (email_id, memberid, sent) VALUES ($1, $2, $3)',
                    [$eid, $member, $already_sent]
                );
            }
        }

        return new self($eid);
    }

    private static function addFooter($message)
    {
        $footer = 'This email was sent automatically from MyRadio. '
            .'You can opt out of emails by visiting '.URLUtils::makeURL('Profile', 'edit').'.';
        return $message.self::$rtnl.self::$rtnl.$footer;
    }

    private static function addHTMLFooter($message)
    {
        $html_footer = 'This email was sent automatically from MyRadio. '
            .'You can opt out of emails <a href="'.URLUtils::makeURL('Profile', 'edit').'">on your profile page</a>.';
        return $message.'<br><hr>'.$html_footer;
    }

    /**
     * Actually send the email.
     */
    public function send()
    {
        //Don't send if it's scheduled in the future.
        if ($this->timestamp > time()) {
            return;
        }
        $this->body_transformed = utf8_encode($this->body_transformed);
        /** @var MyRadio_User $user */
        foreach ($this->getUserRecipients() as $user) {
            if (!$this->getSentToUser($user)) {
                //Don't send if the user has opted out
                if ($user->getReceiveEmail()) {
                    $u_subject = trim(str_ireplace('#NAME', $user->getFName(), $this->subject));
                    if (substr($u_subject, 0, 1) !== '[') {
                        $u_subject = '['.Config::$short_name.'] '.$u_subject;
                    }
                    $this->mailer->Subject = $u_subject;
                    $this->mailer->Body = str_ireplace('#NAME', $user->getFName(), $this->body);
                    if (!empty($this->body_alt)) {
                        $this->mailer->AltBody = str_ireplace('#NAME', $user->getFName(), $this->body_alt);
                    }
                    try {
                        $this->mailer->addAddress($user->getEmail(), $user->getName());
                        $this->mailer->send();
                    } catch (\Exception $e) {
                        trigger_error($e->getMessage(), E_USER_NOTICE);
                    }
                }
                $this->setSentToUser($user);
                $this->mailer->clearAddresses();
            }
        }

        /** @var MyRadio_List $list */
        foreach ($this->getListRecipients() as $list) {
            if (!$this->getSentToList($list)) {
                /** @var MyRadio_User $user */
                foreach ($list->getMembers() as $user) {
                    //Don't send if the user has opted out
                    if ($user->getReceiveEmail()) {
                        $u_subject = trim(str_ireplace('#NAME', $user->getFName(), $this->subject));
                        if (substr($u_subject, 0, 1) !== '[') {
                            $u_subject = '['.Config::$short_name.'] '.$u_subject;
                        }
                        $this->mailer->Subject = $u_subject;
                        $this->mailer->Body = str_ireplace('#NAME', $user->getFName(), $this->body);
                        if (!empty($this->body_alt)) {
                            $this->mailer->AltBody = str_ireplace('#NAME', $user->getFName(), $this->body_alt);
                        }
                        try {
                            $this->mailer->addAddress($user->getEmail(), $user->getName());
                            $this->mailer->send();
                        } catch (\Exception $e) {
                            trigger_error($e->getMessage(), E_USER_NOTICE);
                        }
                    }
                    $this->mailer->clearAddresses();
                }
                $this->setSentToList($list);
            }
        }

        return;
    }

    public function getSentToUser(MyRadio_User $user)
    {
        $r = self::$db->fetchColumn(
            'SELECT sent FROM mail.email_recipient_member WHERE email_id=$1 AND memberid=$2 LIMIT 1',
            [$this->email_id, $user->getID()]
        );
        return $r[0] === 't';
    }

    public function setSentToUser(MyRadio_User $user)
    {
        self::$db->query(
            'UPDATE mail.email_recipient_member SET sent=\'t\' WHERE email_id=$1 AND memberid=$2',
            [$this->email_id, $user->getID()]
        );
        $this->updateCacheObject();
    }

    public function getSentToList(MyRadio_List $list)
    {
        $r = self::$db->fetchColumn(
            'SELECT sent FROM mail.email_recipient_list WHERE email_id=$1 AND listid=$2 LIMIT 1',
            [$this->email_id, $list->getID()]
        );
        return $r[0] === 't';
    }

    public function setSentToList(MyRadio_List $list)
    {
        self::$db->query(
            'UPDATE mail.email_recipient_list SET sent=\'t\' WHERE email_id=$1 AND listid=$2',
            [$this->email_id, $list->getID()]
        );
        $this->updateCacheObject();
    }

    /**
     * Sends an email to the specified User.
     *
     * @param MyRadio_User $to
     * @param string       $subject email subject
     * @param sting        $message email message
     *
     * @todo Check if "Receive Emails" is enabled for the User
     */
    public static function sendEmailToUser(MyRadio_User $to, $subject, $message, MyRadio_User $from = null)
    {
        self::create(['members' => [$to]], $subject, $message, $from);

        return true;
    }

    /**
     * Sends an email to the specified MyRadio_List.
     *
     * @param MyRadio_List  $to
     * @param string        $subject email subject
     * @param string        $message email message
     *
     * @todo Check if "Receive Emails" is enabled for the User
     */
    public static function sendEmailToList(MyRadio_List $to, $subject, $message, MyRadio_User $from = null)
    {
        if ($from !== null && !$to->hasSendPermission($from)) {
            return false;
        }
        self::create(['lists' => [$to]], $subject, $message, $from);

        return true;
    }

    /**
     * Sends an email to all the specified Users, with certain customisation abilities:
     * #NAME is replaced with the User's first name.
     *
     * @param array  $to      An array of User objects
     * @param string $subject email subject
     * @param sting  $message email message
     */
    public static function sendEmailToUserSet($to, $subject, $message, MyRadio_User $from = null)
    {
        foreach ($to as $user) {
            if (!($user instanceof MyRadio_User)) {
                throw new MyRadioException($user.' is not an instance or derivative of the user class!');
            }
        }
        self::create(['members' => $to], $subject, $message, $from);
    }

    /**
     * Returns if the User received this email.
     *
     * Will return true if the email was sent to a mailing list they were
     * not a member of at the time.
     *
     * @param MyRadio_User $user
     *
     * @return bool
     */
    public function isRecipient(MyRadio_User $user)
    {
        foreach ($this->r_users as $ruser) {
            if ($ruser === $user->getID()) {
                return true;
            }
        }
        foreach ($this->getListRecipients() as $list) {
            if ($list->isMember($user->getID())) {
                return true;
            }
        }

        return false;
    }

    public function getSubject()
    {
        return $this->subject;
    }

    public function getListRecipients()
    {
        return MyRadio_List::resultSetToObjArray($this->r_lists);
    }

    public function getUserRecipients()
    {
        return MyRadio_User::resultSetToObjArray($this->r_users);
    }

    public function getViewableBody()
    {
        if ($this->body) {
            $data = CoreUtils::getSafeHTML($this->body);
        } else {
            /*
             * @todo Filtering here.
             */
            $body = $this->body_transformed;
            $data = CoreUtils::getSafeHTML($body);
        }

        if (strpos($data, '<') === false) {
            return nl2br($data);
        } else {
            return $data;
        }
    }

    public function getID()
    {
        return $this->email_id;
    }

    /**
     * @mixin body Also returns the body of the email.
     */
    public function toDataSource($mixins = [])
    {
        $mixin_funcs = [
            'body' => function (&$data) {
                $data['body'] = $this->getViewableBody();
            },
        ];

        $data = [
            'email_id' => $this->getID(),
            'from' => empty($this->from) ? null : $this->from->getName(),
            'timestamp' => $this->timestamp,
            'subject' => $this->subject,
            'view' => [
                'display' => 'icon',
                'value' => 'envelope',
                'title' => 'Read this email',
                'url' => URLUtils::makeURL('Mail', 'view', ['emailid' => $this->getID()]),
            ],
        ];

        $this->addMixins($data, $mixins, $mixin_funcs);
        return $data;
    }

    /**
     * BELOW HERE IS FOR IF STUFF BREAKS REALLY EARLY BEFORE ^ WILL WORK *.
     */

    /**
     * @param string $subject email subject
     * @param sting  $message email message
     */
    public static function sendEmailToComputing($subject, $message)
    {
        mail(
            'MyRadio Service <'.Config::$error_report_email.'@'.Config::$email_domain.'>',
            $subject,
            self::addFooter($message),
            self::getDefaultHeader()
        );
        return true;
    }

    /**
     * @return string default headers for sending email - Plain text and sent from no-reply
     */
    private static function getDefaultHeader()
    {
        $headers = 'Content-type: text/plain; charset=utf-8';
        $sender = 'From: MyRadio <no-reply@'.Config::$email_domain.'>';
        return $headers.self::$rtnl.$sender;
    }
}
