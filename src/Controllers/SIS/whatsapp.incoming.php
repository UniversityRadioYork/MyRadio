<?php

use MyRadio\Config;
use MyRadio\MyRadioException;
use MyRadio\ServiceAPI\MyRadio_Timeslot;

if (!Config::$sis_whatsapp_enable) {
    throw new MyRadioException('SIS Whatsapp is disabled', 404);
}

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    if (!array_key_exists('hub_mode', $_GET) || !array_key_exists('hub_challenge', $_GET) || !array_key_exists('hub_verify_token', $_GET)) {
        error_log('missing get parameter');
        throw new MyRadioException('Missing get parameters', 400);
    }
    $hub_mode = $_GET['hub_mode'];
    $hub_challenge = $_GET['hub_challenge'];
    $hub_verify_token = $_GET['hub_verify_token'];
    if ($hub_mode !== 'subscribe') {
        error_log('wrong hub mode');
        throw new MyRadioException('Incorrect mode', 400);
    }
    if ($hub_verify_token !== Config::$sis_whatsapp_verify_token) {
        error_log('incorrect verify token');
        throw new MyRadioException('Incorrect verify token', 400);
    }
    header('Content-Type: text/plain');
    echo $hub_challenge;
} else if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_SERVER['CONTENT_TYPE']) && strpos($_SERVER['CONTENT_TYPE'], 'application/json') !== false) {
        $raw = file_get_contents('php://input') ?: '';
        $data = json_decode($raw, true) ?: [];
    } else {
        $data = $_REQUEST;
    }

    $timeslot = MyRadio_Timeslot::getCurrentTimeslot();
    if ($timeslot !== null) {
        foreach ($data['entry'] as $entry) {
            foreach ($entry['changes'] as $change) {
                $contacts = $change['value']['contacts'];
                $messages = $change['value']['messages'];
                foreach ($messages as $message) {
                    $from = $message['from'];
                    $from_name = 'Unknown';
                    foreach ($contacts as $contact) {
                        if ($contact['wa_id'] === $from) {
                            $from_name = $contact['profile']['name'];
                        }
                    }
                    $type = $message['type'];
                    $body = '';
                    if (array_key_exists('text', $message)) {
                        $body = $message['text']['body'];
                    }
                    if ($body !== '') {
                        $timeslot->sendWhatsappMessage($body, $from_name, $from);
                    }
                }
            }
        }
    }
}
