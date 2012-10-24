<?php
/**
 * 
 * @todo Proper Documentation
 * @author Lloyd Wallis <lpw@ury.org.uk>
 * @version 21072012
 * @package MyURY_Scheduler
 */

$demos = MyURY_Demo::listDemos();
print_r($demos);
require 'Views/MyURY/Scheduler/demoList.php';