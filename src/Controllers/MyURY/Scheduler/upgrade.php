<?php
/**
 * Script to migrate shows from the old schema to Scheduler 2.0
 * 
 * Currently only migrates "No Show Scheduled"
 */

$db = Database::getInstance();

function getTimeslotsForSeason($season_id) {
  //Gets a list of timeslots for a "Season"
  global $db;
  $data = $db->fetch_all('SELECT * FROM sched_timeslot WHERE entryid=$1 ORDER BY starttime ASC', array($season_id));
  for ($i = 0; $i < sizeof($data); $i++) {
    $data[$i]['duration'] = strtotime($data[$i]['endtime']) - strtotime($data[$i]['starttime']);
  }
}

function getPresentersForSeason($season_id) {
  //Gets a list of presenters for a "Season"
  global $db;
  return $db->fetch_column('SELECT memberid FROM sched_memberentry WHERE entryid=$1', array($season_id));
}

//Type = 3 Limits to shows
$shows = $db->fetch_all('SELECT * FROM sched_entry WHERE summary=\'No Show Scheduled\' AND entrytypeid=3 ORDER BY summary');

$previousshow = '';
$show_seasoned = array();
for ($i = 0; $i < sizeof($shows); $i++) {
  if ($previousshow !== $shows[$i]['summary']) {
    //This is a new show, not a continuation. Stash the current show and reset
    if (!empty($seasons)) {
      echo '</details>End of show '.$previousshow.'<br>';
      $seasons['info'] => $show_meta;
      $show_seasoned[$previousshow] = $seasons;
      echo '<details>'.nl2br(print_r($seasons,true)).'</details>';
    }
    $seasons = array();
    $show_meta = array('created' => strtotime('+10 Years'));
    $previousshow = $shows[$i]['summary'];
    $season_number = 1;
    echo '<div style="background-color:#ccc">New Show: '.$previousshow.'</div><details>';
  }
  
  //Continue with the current show, adding the new season
  echo 'Season '.$season_number.'<br><details>';
  
  $season = array(
      'timeslots' => getTimeslotsForSeason($shows[$i]['entryid']),
      'presenters' => getPresentersForSeason($shows[$i]['entryid']),
      'info' => $shows[$i]
      );
  echo nl2br(print_r($season, true));
  echo '</details>';
  $season_number++;
}
echo '</details>';
echo nl2br(print_r($shows, true));