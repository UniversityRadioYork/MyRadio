<?php
/**
 * This is pretty much what every Controller should look like.
 * Some might include more than one model etc.... 
 * 
 * @todo proper documentation
 * 
 * @author Lloyd Wallis <lpw@ury.org.uk>
 * @version 20130718
 * @package MyRadio_Core
 */
MyRadioNews::markNewsAsRead((int)$_REQUEST['newsentryid'], User::getInstance());
require 'Views/MyRadio/nocontent.php';