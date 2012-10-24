<?php
/**
 * Allows URY Trainers to create demo slots for new members to attend
 * 
 * @author Lloyd Wallis <lpw@ury.org.uk>
 * @version 24102012
 * @package MyURY_Scheduler
 */

$result = MyURY_Demo::attend($_REQUEST['demoid']);

switch ($result) {
  case 0:
    echo 'You have been scheduled to attend this demo.';
    break;
  case 1:
    echo 'Sorry, this demo is already full. Only two new members may attend a demo.';
    break;
  case 2:
    echo 'Sorry, an error occurred trying to register you for this demo.';
    break;
}