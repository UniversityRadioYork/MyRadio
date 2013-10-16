<?php
/**
 * Presents a form to the user to enable them to cancel an Episode
 * 
 * @author Lloyd Wallis <lpw@ury.org.uk>
 * @version 20131016
 * @package MyURY_Scheduler
 */

if (!isset($_REQUEST['show_season_timeslot_id'])) {
  throw new MyURYException('No timeslotid provided.', 400);
}
//The Form definition
require 'Models/Scheduler/reasonfrm.php';
//'tis a one line view
$form->render();