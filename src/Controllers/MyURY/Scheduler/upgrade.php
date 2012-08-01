<?php
/**
 * Script to migrate shows from the old schema to Scheduler 2.0
 * 
 * Currently only migrates "No Show Scheduled"
 */

$db = Database::getInstance();

//Type = 3 Limits to shows
$shows = $db->fetch_all('SELECT * FROM sched_entry WHERE summary=\'No Show Scheduled\' AND entrytypeid=3 ORDER BY summary');

$previousshow = '';
$show_seasoned = array();
for ($i = 0; $i < sizeof($shows); $i++) {
  if ($previousshow !== $shows[$i]['summary']) {
    //This is a new show, not a continuation. Stash the current show and reset
    if (!empty($seasons)) {
      echo 'End of show '.$previousshow.'<br>';
      $show_seasoned[$previousshow] = $seasons;
      echo '<details>'.nl2br(print_r($seasons,true)).'</details>';
    }
    $seasons = array();
    $previousshow = $shows[$i]['summary'];
    echo '<div style="background-color:#ccc">New Show: '.$previousshow.'</div>';
  }
  
  //Continue with the current show, adding the new season
}
//Now for each show, we check if it's a different show or just a new season


echo nl2br(print_r($shows, true));