<?php

use MyRadio\Config;
use \MyRadio\MyRadioException;
use \MyRadio\MyRadio\URLUtils;
use \MyRadio\ServiceAPI\MyRadio_Quote;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $quote = MyRadio_Quote::getInstance($data['quote_id']);
    $data = $quote->getRemoveForm()->readValues();
    $result = $quote->removeQuote($data['reason']);

    if (!$result) {
        $message = 'The quote was unable to be removed or you do not have the required permissions, '
            .'please contact computing@'.Config::$email_domain.' instead.';
    } else {
        $message = 'Your quote removal has been processed.';
    }

    URLUtils::backWithMessage($message);
} else {
    if (!isset($_REQUEST['quote_id'])) {
        throw new MyRadioException('No quote_id provided', 400);
    }
    MyRadio_Quote::getInstance($_REQUEST['quote_id'])->getRemoveForm()->render();
}
