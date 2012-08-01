<?php
/**
 * Script to migrate shows from the old schema to Scheduler 2.0
 * 
 * Currently only migrates "No Show Scheduled"
 */
ob_start();
$db = Database::getInstance();
$approving_user = 7449;

function getTimeslotsForSeason($season_id) {
  //Gets a list of timeslots for a "Season"
  global $db;
  $data = $db->fetch_all('SELECT starttime,endtime FROM sched_timeslot WHERE entryid=$1 ORDER BY starttime ASC', array($season_id));
  for ($i = 0; $i < sizeof($data); $i++) {
    $data[$i]['duration'] = strtotime($data[$i]['endtime']) - strtotime($data[$i]['starttime']);
    unset($data[$i]['endtime']);
  }
  return $data;
}

function getPresentersForSeason($season_id) {
  //Gets a list of presenters for a "Season"
  global $db;
  return $db->fetch_column('SELECT memberid FROM sched_memberentry WHERE entryid=$1', array($season_id));
}

function getStudioForSeason($season_id) {
  //Gets the studio for a "Season"
  global $db;
  $data = $db->fetch_column('SELECT roomid FROM sched_roomentry WHERE entryid=$1', array($season_id));
  switch ($data[0]) {
    case 1:
      return 1;
      break;
    case 3:
      return 2;
      break;
    case 4:
      return 3;
      break;
    default:
      throw new MyURYException('Invalid Room Pointer');
  }
}

function timeToTimestamp($time) {
  return date('Y-m-d H:i:sO', $time);
}

//Type = 3 Limits to shows
$shows = $db->fetch_all('SELECT * FROM sched_entry WHERE summary=\'No Show Scheduled\' AND entrytypeid=3 ORDER BY summary, entryid');
echo '<div class="left">';
$previousshow = '';
$show_seasoned = array();
for ($i = 0; $i <= sizeof($shows); $i++) {
  if ($previousshow !== @$shows[$i]['summary']) {
    //This is a new show, not a continuation. Stash the current show and reset
    if (!empty($seasons)) {
      echo '</details><br>';
      $seasons['info'] = $show_meta;
      $show_seasoned[$previousshow] = $seasons;
    }
    if (empty($shows[$i]['summary'])) continue;
    $seasons = array();
    $show_meta = array('created' => strtotime('+10 Years'));
    $previousshow = $shows[$i]['summary'];
    $season_number = 1;
    echo '<div style="background-color:#ccc">New Show: '.$previousshow.'</div><details>';
  }
  
  //Continue with the current show, adding the new season
  echo 'Season '.$season_number.'<br><details style="margin-left:20px">';
  
  $season = array(
      'timeslots' => getTimeslotsForSeason($shows[$i]['entryid']),
      'presenters' => getPresentersForSeason($shows[$i]['entryid']),
      'description' => $shows[$i]['description']
      );
  echo nl2br(print_r($season, true));
  $seasons[] = $season;
  echo '</details>';
  $season_number++;
}

echo '<details>'.nl2br(print_r($show_seasoned, true)).'</details>';

//Reset
$db->query('DELETE FROM schedule.show');

foreach ($show_seasoned as $name => $show) {
  $owner = $show[0]['presenters'][0];
  $submitted = timeToTimestamp($show['info']['created']);
  $db->query('INSERT INTO schedule.show (show_type_id, submitted, memberid) VALUES (1, $1, $2)',
          array($submitted, $owner));
}

echo '</div>';

$twig = CoreUtils::getTemplateObject();
$twig->addVariable('serviceName', 'Configuration')
     ->addVariable('serviceVersion', 'Experimental')
     ->setTemplate('stripe.twig')
     ->addVariable('title', $module)
     ->addVariable('stripedata', ob_get_clean())
     ->render();