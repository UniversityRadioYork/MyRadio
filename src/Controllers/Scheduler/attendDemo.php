<?php
/**
 * Allows URY Trainers to create demo slots for new members to attend
 * 
 * @author Lloyd Wallis <lpw@ury.org.uk>
 * @version 24102012
 * @package MyURY_Scheduler
 */

$result = MyURY_Demo::attend($_REQUEST['demoid']);
header('Location: '.CoreUtils::makeURL($module, 'listDemos', array('msg'=>$result)));