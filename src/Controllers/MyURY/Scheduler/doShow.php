<?php
/**
 * 
 * @todo Proper Documentation
 * @author Lloyd Wallis <lpw@ury.org.uk>
 * @version 21072012
 * @package MyURY_Scheduler
 */

//The Form definition
require 'Models/MyURY/Scheduler/showfrm.php';

echo nl2br(print_r($form->readValues(),true));

print_r(MyURY_Show::create($form->readValues()));