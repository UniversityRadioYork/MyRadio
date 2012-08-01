<?php
/**
 * Script to migrate shows from the old schema to Scheduler 2.0
 * 
 * Currently only migrates "No Show Scheduled"
 */

$db = Database::getInstance();

//Type = 3 Limits to shows
$shows = $db->fetch_all('SELECT * FROM sched_entry WHERE entrytypeid=3 GROUP BY summary');

echo nl2br(print_r($shows, true));