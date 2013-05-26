<?php

class MyURY_EmailQueueDaemon {
  public static function isEnabled() { return true; }
  
  public static function run() {
    //Get up to 5 unsent emails
    $db = Database::getInstance();
    
    $result = $db->fetch_column('SELECT emailid FROM mail.email WHERE
      (email_id IN (SELECT DISTINCT email_id FROM mail.email_recipient_user WHERE sent=\'f\')
      OR email_id IN (SELECT DISTINCT email_id FROM mail.email_recipient_list WHERE sent=\'f\')
      AND timestamp > NOW() LIMIT 1');
    
    foreach ($result as $email) {
      echo "Sending email $email\n";
      MyURY_Email::getInstance($email)->send();
    }
  }
}