<?php

/**
 * This file provides the MyRadioError class for MyRadio
 * @package MyRadio_Core
 */

/**
 * Provides email functions so that MyRadio can send email.
 * 
 * @author Andy Durant <aj@ury.org.uk>
 * @author Lloyd Wallis <lpw@ury.org.uk>
 * @version 20130809
 * @package MyRadio_Mail
 * @todo Footers contain hard-coded URLs. This used to be necessary (when the links went to mint), but isn't now.
 */
class MyRadioEmail extends ServiceAPI {
  
  // Defaults
  /**
   * @todo Hardcoded URLs
   */
  private static $headers = 'Content-type: text/plain; charset=utf-8';
  private static $sender = 'From: MyRadio <no-reply@ury.org.uk>';
  private static $footer = 'This email was sent automatically from MyRadio. You can opt out of emails by visiting https://ury.org.uk/myury/Profile/edit/.';
  private static $html_footer = 'This email was sent automatically from MyRadio. You can opt out of emails <a href="https://ury.org.uk/myury/Profile/edit/">on your profile page</a>.';
  // Standard
  /**
   * @var string carriage return + newline
   */
  private static $rtnl = "\r\n";
  private static $multipart_boundary = 'muryp2c6cf41f304e3';
  private $email_id;
  private $r_lists = array();
  private $r_users = array();
  private $subject;
  private $body;
  private $body_transformed;
  private $multipart = false;
  private $from;
  private $timestamp;

  protected function __construct($eid) {
    self::$db = Database::getInstance();

    $info = self::$db->fetch_one('SELECT * FROM mail.email WHERE email_id=$1', array($eid));

    if (empty($info)) {
      throw new MyRadioException('Email ' . $eid . ' does not exist!');
    }

    $this->subject = $info['subject'];
    $this->body = $info['body'];
    $this->from = (empty($info['sender']) ? null : MyRadio_User::getInstance($info['sender']));
    $this->timestamp = strtotime($info['timestamp']);
    $this->email_id = $eid;

    $this->r_users = self::$db->fetch_column('SELECT memberid FROM mail.email_recipient_member WHERE email_id=$1', array($eid));

    $this->r_lists = self::$db->fetch_column('SELECT listid FROM mail.email_recipient_list WHERE email_id=$1', array($eid));

    /**
     * Check if the body needs to be split into multipart.
     * This creates a string with both Text and HTML parts.
     */
    $split = strip_tags($this->body);
    if ($this->body !== $split) {
      //There's HTML in there
      $this->multipart = true;
      $this->body_transformed = 'This is a MIME encoded message.'
              . self::$rtnl . self::$rtnl . '--' . self::$multipart_boundary . self::$rtnl
              . 'Content-Type: text/plain;charset=utf-8' . self::$rtnl . self::$rtnl
              . self::addFooter($split) . self::$rtnl . self::$rtnl . '--' . self::$multipart_boundary . self::$rtnl
              . 'Content-Type: text/html;charset=utf-8' . self::$rtnl . self::$rtnl
              . self::addHTMLFooter($this->body) . self::$rtnl . self::$rtnl . '--' . self::$multipart_boundary . '--';
    } else {
      $this->body_transformed = self::addFooter($this->body);
    }
  }

  /**
   * Create a new email
   * @param MyRadio_User $from The User who sent the email. If null, uses no-reply
   * @param array $to A 2D array of 'lists' = [l1, l2], 'members' = [m1, m2]
   * @param String $subject email subject
   * @param String $body email body
   * @param int $timestamp Send time. If null, use now.
   * @param bool $already_sent If true, all Recipients will be set to having had the email sent.
   */
  public static function create($to, $subject, $body, $from = null, $timestamp = null, $already_sent = false) {
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
      $caller =  array_shift(debug_backtrace());
      trigger_error('Received long email body: '.strlen($body).' bytes. Source: '
              .$from.'/'.$caller['file'].':'.$caller['line'], E_USER_NOTICE);
    }
    
    $params = array($subject, trim($body));
    if ($timestamp !== null) {
      $params[] = CoreUtils::getTimestamp($timestamp);
    }
    if ($from !== null) {
      $params[] = $from->getID();
    }

    $eid = self::$db->fetch_column('INSERT INTO mail.email (subject, body'
            . ($timestamp !== null ? ', timestamp' : '') . ($from !== null ? ', sender' : '') . ')
              VALUES ($1, $2' . (($timestamp !== null or $from !== null) ? ', $3' : '')
            . (($timestamp !== null && $from !== null) ? ', $4' : '') . ') RETURNING email_id'
            , $params);

    $eid = $eid[0];
    
    if (empty($eid)) {
      throw new MyRadioException('Failed to create email. See previous error.');
    }

    if (!empty($to['lists'])) {
      foreach ($to['lists'] as $list) {
        if (is_object($list)) {
          $list = $list->getID();
        }
        self::$db->query('INSERT INTO mail.email_recipient_list (email_id, listid, sent) VALUES ($1, $2, $3)',
                array($eid, $list, $already_sent));
      }
    }
    if (!empty($to['members'])) {
      foreach ($to['members'] as $member) {
        if (is_object($member)) {
          $member = $member->getID();
        }
        self::$db->query('INSERT INTO mail.email_recipient_member (email_id, memberid, sent) VALUES ($1, $2, $3)',
                array($eid, $member, $already_sent));
      }
    }

    return new self($eid);
  }

  private function getHeader() {
    $headers = array('MIME-Version: 1.0');

    if ($this->from !== null) {
      $headers[] = 'From: ' . $this->from->getName() . ' <' . $this->from->getEmail() . '>';
      $headers[] = 'Return-Path: ' . $this->from->getEmail();
    } else {
      $headers[] = 'From: '.Config::$long_name.' <no-reply@'.Config::$email_domain.'>';
      $headers[] = 'Return-Path: no-reply@'.Config::$email_domain;
    }

    /**
     * !! Multipart headers must be *last* or things Go Badly
     */
    if ($this->multipart) {
      $headers[] = 'Content-Type: multipart/alternative;boundary=' . self::$multipart_boundary;
    } else {
      $headers[] = 'Content-Type: text/plain; charset=utf-8';
    }

    return implode(self::$rtnl, $headers);
  }

  private static function addFooter($message) {
    return $message . self::$rtnl . self::$rtnl . self::$footer;
  }

  private static function addHTMLFooter($message) {
    return $message . '<hr>' . self::$html_footer;
  }

  /**
   * Actually send the email
   */
  public function send() {
    //Don't send if it's scheduled in the future.
    if ($this->timestamp > time()) {
      return;
    }
    $this->body_transformed = utf8_encode($this->body_transformed);
    foreach ($this->getUserRecipients() as $user) {
      if (!$this->getSentToUser($user)) {
        //Don't send if the user has opted out
        if ($user->getReceiveEmail()) {
          $u_subject = str_ireplace('#NAME', $user->getFName(), $this->subject);
          $u_message = str_ireplace('#NAME', $user->getFName(), $this->body_transformed);
          if (!mail($user->getName() . ' <' . $user->getEmail() . '>', '['.Config::$short_name.'] ' . $u_subject, $u_message, $this->getHeader())) {
            continue;
          }
        }
        $this->setSentToUser($user);
      }
    }

    foreach ($this->getListRecipients() as $list) {
      if (!$this->getSentToList($list)) {
        foreach ($list->getMembers() as $user) {
          //Don't send if the user has opted out
          if ($user->getReceiveEmail()) {
            $u_subject = str_ireplace('#NAME', $user->getFName(), $this->subject);
            $u_message = str_ireplace('#NAME', $user->getFName(), $this->body_transformed);
            if (!mail($list->getName() . ' <' . $user->getEmail() . '>', '['.Config::$short_name.'] ' . $u_subject, $u_message, $this->getHeader())) {
              continue;
            }
          }
        }
        $this->setSentToList($list);
      }
    }

    return;
  }

  public function getSentToUser(MyRadio_User $user) {
    $r = self::$db->fetch_column('SELECT sent FROM mail.email_recipient_member WHERE email_id=$1 AND memberid=$2 LIMIT 1', array($this->email_id, $user->getID()));

    return $r[0] === 't';
  }

  public function setSentToUser(MyRadio_User $user) {
    self::$db->query('UPDATE mail.email_recipient_member SET sent=\'t\' WHERE email_id=$1 AND memberid=$2', array($this->email_id, $user->getID()));
    $this->updateCacheObject();
  }

  public function getSentToList(MyRadio_List $list) {
    $r = self::$db->fetch_column('SELECT sent FROM mail.email_recipient_list WHERE email_id=$1 AND listid=$2 LIMIT 1', array($this->email_id, $list->getID()));

    return $r[0] === 't';
  }

  public function setSentToList(MyRadio_List $list) {
    self::$db->query('UPDATE mail.email_recipient_list SET sent=\'t\' WHERE email_id=$1 AND listid=$2', array($this->email_id, $list->getID()));
    $this->updateCacheObject();
  }

  /**
   * Sends an email to the specified User
   * @param MyRadio_User $to
   * @param string $subject email subject
   * @param sting $message email message
   * @todo Check if "Receive Emails" is enabled for the User
   */
  public static function sendEmailToUser(MyRadio_User $to, $subject, $message, $from = null) {
    self::create(array('members' => array($to)), $subject, $message, $from);
    return true;
  }

  /**
   * Sends an email to the specified MyRadio_List
   * @param MyRadio_List $to
   * @param string $subject email subject
   * @param sting $message email message
   * @todo Check if "Receive Emails" is enabled for the User
   */
  public static function sendEmailToList(MyRadio_List $to, $subject, $message, $from = null) {
    if ($from !== null && !$to->hasSendPermission($from)) {
      return false;
    }
    self::create(array('lists' => array($to)), $subject, $message, $from);
    return true;
  }

  /**
   * Sends an email to all the specified Users, with certain customisation abilities:
   * #NAME is replaced with the User's first name
   * 
   * @param Array $to An array of User objects
   * @param string $subject email subject
   * @param sting $message email message
   */
  public static function sendEmailToUserSet($to, $subject, $message, $from = null) {

    foreach ($to as $user) {
      if (!($user instanceof MyRadio_User)) {
        throw new MyRadioException($user . ' is not an instance or derivative of the user class!');
      }

      self::create(array('members' => $to), $subject, $message, $from);
    }
  }
  
  /**
   * Returns if the User received this email.
   * 
   * Will return true if the email was sent to a mailing list they were
   * not a member of at the time.
   * 
   * @param MyRadio_User $user
   * @return boolean
   */
  public function isRecipient(MyRadio_User $user) {
    foreach ($this->r_users as $ruser) {
      if ($ruser === $user->getID()) {
        return true;
      }
    }
    foreach ($this->getListRecipients() as $list) {
      if ($list->isMember($user)) {
        return true;
      }
    }
    return false;
  }
  
  public function getSubject() {
    return $this->subject;
  }
  
  public function getListRecipients() {
    return MyRadio_List::resultSetToObjArray($this->r_lists);
  }
  
  public function getUserRecipients() {
    return MyRadio_User::resultSetToObjArray($this->r_users);
  }
  
  public function getViewableBody() {
    if ($this->body) {
      $data = CoreUtils::getSafeHTML($this->body);
    } else {
      /**
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
  
  public function getID() {
    return $this->email_id;
  }
  
  public function toDataSource($full = false) {
    $data = [
        'email_id' => $this->getID(),
        'from' => empty($this->from) ? null : $this->from->getName(),
        'timestamp' => CoreUtils::happyTime($this->timestamp),
        'subject' => $this->subject,
        'view' => [
            'display' => 'icon',
            'value' => 'mail-open',
            'title' => 'Read this email',
            'url' => CoreUtils::makeURL('Mail', 'view', ['emailid' => $this->getID()])
        ]
    ];
    
    if ($full) {
      $data['body'] = $this->getViewableBody();
    }
    
    return $data;
  }

  /** BELOW HERE IS FOR IF STUFF BREAKS REALLY EARLY BEFORE ^ WILL WORK * */

  /**
   * 
   * @param string $subject email subject
   * @param sting $message email message
   */
  public static function sendEmailToComputing($subject, $message) {
    mail("MyRadio Service <alerts.myury@ury.org.uk>", $subject, self::addFooter($message), self::getDefaultHeader());
    return TRUE;
  }

  /**
   * 
   * @return string default headers for sending email - Plain text and sent from no-reply
   */
  private static function getDefaultHeader() {
    return self::$headers . self::$rtnl . self::$sender;
  }

}

