<?php
/**
 * 
 * @todo Proper Documentation
 * @author Lloyd Wallis <lpw@ury.org.uk>
 * @version 21072012
 * @package MyURY_Scheduler
 */
CoreUtils::requirePermission(AUTH_ALLOCATESLOTS);

//The Form definition
require 'Models/MyURY/Scheduler/allocatefrm.php';
print_r($_REQUEST);
print_r($form->readValues());

throw new MyURYException('Not Implemented', MyURYException::FATAL);