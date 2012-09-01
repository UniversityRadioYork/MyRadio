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
  private static $sender = 'From: MyURY <no-reply@ury.york.ac.uk>';
  private static $footer = 'This email was sent automatically from MyURY';
  
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


