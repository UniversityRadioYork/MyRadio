<?php
/**
 * Script to migrate shows from the old schema to Scheduler 2.0
 * 
 * Currently only migrates "No Show Scheduled"
 */

$db = Database::getInstance();

$shows = $db->fetch_all('SELECT * FROM sched_entry WHERE summary=\'No Show Scheduled\'');

echo nl2br(print_r($shows, true));