<?php
/**
 * This file provides the MyURYError class for MyURY
 * @package MyURY_Core
 */

/**
 * Provides email functions so that MyURY can send email.
 * 
 * @author Andy Durant <aj@ury.org.uk>
 * @version 03082012
 * @package MyURY_Core
 */
class MyURYEmail {
  // Defaults
  private static $headers = 'Content-type: text/plain; charset=utf-8';
  private static $sender = 'From: MyURY <no-reply@ury.org.uk>';
  private static $footer = 'This email was sent automatically from MyURY. You can opt out of URY Emails by visiting https://ury.york.ac.uk/members/memberadmin/edit.php.';
  
  // Standard
  /**
   * @var string carriage return + newline
   */
  private static $rtnl = "\r\n";

  /**
   * 
   * @return string default headers for sending email - Plain text and sent from no-reply
   */
  private static function getDefaultHeader() {
    return self::$headers . self::$rtnl . self::$sender;
  }
  /**
   * 
   * @param string $from email address or "Name <email>"
   * @return string The header line for From:
   */
  private static function setSender($from) {
    return self::$headers . self::$rtnl . 'From: ' . $from;
  }
  private static function addFooter($message) {
    return $message.self::$rtnl.self::$rtnl.self::$footer;
  }

  /**
   * 
   * @param string $to email address or "Name <email>"
   * @param string $subject email subject
   * @param sting $message email message
   */
  public static function sendEmail($to, $subject, $message){
    mail($to, $subject, self::addFooter($message), self::getDefaultHeader());
    return TRUE;
  }
  /**
   * Sends an email to the specified User
   * @param User $to
   * @param string $subject email subject
   * @param sting $message email message
   * @todo Check if "Receive Emails" is enabled for the User
   */
  public static function sendEmailToUser(User $to, $subject, $message) {
    self::sendEmail($to->getName() . ' <' . $to->getEmail() . '>', $subject, $message);
  }
  /**
   * Sends an email to all the specified Users, with certain customisation abilities:
   * #NAME is replaced with the User's first name
   * 
   * @param Array $to An array of User objects
   * @param string $subject email subject
   * @param sting $message email message
   * @todo Some more replacement strings?
   * @todo Make the replacement string feature a standard method of User? Might have other uses.
   */
  public static function sendEmailToUserSet($to, $subject, $message) {
    
    foreach ($to as $user) {
      if (!is_a($user, User)) {
        throw new MyURYException($user .' is not an instance of User or a derivative!');
      }
      
      $u_subject = $subject;
      $u_message = $message;
      
      $u_subject = str_ireplace('#NAME', $user->getFName(), $u_subject);
      $u_message = str_ireplace('#NAME', $user->getFName(), $u_message);
      
      self::sendEmailToUser($user, $u_subject, $u_message);
      
    }
  }
  /**
   * 
   * @param string $to email address or "Name <email>"
   * @param string $from email address or "Name <email>"
   * @param string $subject email subject
   * @param sting $message email message
   */
  public static function sendEmailFrom($to, $from, $subject, $message) {
    mail($to, $subject, $message, self::setSender($from));
    return TRUE;
  }
  /**
   * 
   * @param string $to email address or "Name <email>"
   * @param string $subject email subject
   * @param sting $message email message
   */
  public static function sendEmailFromComputing($to, $subject, $message){
    mail($to, $subject, $message, self::setSender('URY Computing Team <computing@ury.org.uk>'));
    return TRUE;
  }
  /**
   * 
   * @param string $subject email subject
   * @param sting $message email message
   */
  public static function sendEmailToComputing($subject, $message){
    mail("URY Computing Team <computing@ury.org.uk>", $subject, self::addFooter($message), self::getDefaultHeader());
    return TRUE;
  }
}


