<?php
/**
 * This Controller receives a JSONON set from a client and updates the server model and change log.
 *
 * @author  Lloyd Wallis <lpw@ury.org.uk>
 * @version 16042013
 * @package MyRadio_NIPSWeb
 */

use \MyRadio\MyRadioException;
use \MyRadio\MyRadio\CoreUtils;
use \MyRadio\ServiceAPI\MyRadio_Timeslot;
use \MyRadio\NIPSWeb\NIPSWeb_Token;

if (!isset($_POST['clientid'])) {
    throw new MyRadioException('ClientID Required', 400);
}

$data = MyRadio_Timeslot::getInstance(NIPSWeb_Token::getEditTokenTimeslot($_POST['clientid']))->updateShowPlan($_POST);

CoreUtils::dataToJSON($data);
