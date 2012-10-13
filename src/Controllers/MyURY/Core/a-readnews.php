<?php
/**
 * This is pretty much what every Controller should look like.
 * Some might include more than one model etc.... 
 * 
 * @todo proper documentation
 * 
 * @author Lloyd Wallis <lpw@ury.org.uk>
 * @version 28072012
 * @package MyURY_Core
 */
CoreUtils::markNewsAsRead((int)$_REQUEST['newsentryid'], $member);
require 'Views/MyURY/Core/nocontent.php';