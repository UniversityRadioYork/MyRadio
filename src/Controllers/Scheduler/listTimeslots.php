<?php
/**
 * Controller for outputting a Datatable of Seasons within the specified Show
 * @author Lloyd Wallis <lpw@ury.org.uk>
 * @version 26122012
 * @package MyURY_Scheduler
 * @todo This requires manual permission checks as it needs interesting things
 */

$season = MyURY_Season::getInstance($_GET['show_season_id']);
$timeslots = $season->getAllTimeslots();
require 'Views/Scheduler/timeslotList.php';