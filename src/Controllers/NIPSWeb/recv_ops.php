<?php
/**
 * This Controller receives a JSONON set from a client and updates the server model and change log.
 * 
 * @author Lloyd Wallis <lpw@ury.org.uk>
 * @version 16042013
 * @package MyURY_NIPSWeb
 */

if (!isset($_POST['clientid']))
  throw new MyURYException('ClientID Required', 400);

$data = MyURY_Timeslot::getInstance(NIPSWeb_Token::getEditTokenTimeslot($_POST['clientid']))->updateShowPlan($_POST);

require 'Views/MyURY/Core/datatojson.php';