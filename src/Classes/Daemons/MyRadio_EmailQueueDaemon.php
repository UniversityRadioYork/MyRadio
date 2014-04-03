<?php

class MyRadio_EmailQueueDaemon
{
    public static function isEnabled()
    {
        return Config::$d_EmailQueue_enabled;
    }

    public static function run()
    {
        //Get up to 5 unsent emails
        $db = Database::getInstance();

        $result = $db->fetchColumn(
            'SELECT email_id FROM mail.email WHERE
            (email_id IN (SELECT email_id FROM mail.email_recipient_member WHERE sent=\'f\')
            OR email_id IN (SELECT email_id FROM mail.email_recipient_list WHERE sent=\'f\'))
            AND timestamp <= NOW() LIMIT 5'
        );

        foreach ($result as $email) {
            echo "Sending email $email\n";
            MyRadioEmail::getInstance($email)->send();
        }
    }
}
