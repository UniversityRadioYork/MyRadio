<?php
/**
 * 
 * @todo Proper Documentation
 * @author Lloyd Wallis <lpw@ury.org.uk>
 * @version 21072012
 * @package MyURY_Scheduler
 */

$shows = MyURY_Show::getShowsAttachedToUser();
require 'Views/MyURY/Scheduler/showList.php';