<?php
/**
 * Script to migrate shows from the old schema to Scheduler 2.0
 * 
 * Currently only migrates "No Show Scheduled"
 */
if (!isset($_POST['confirm'])) {
  ?>
<form action="?module=Scheduler&action=upgrade" method="post">
  <input type="hidden" name="confirm" />
  <input type="submit" value="I UNDERSTAND THIS WILL RESET THE NEW SCHEDULER SCHEMA AND MUST NOT BE USED IN PRODUCTION" />
</form>
<?php
exit;
}
ob_start();
echo '<div class="ui-state-error">This script deletes all data from the new schedule schema.</div>';
$db = Database::getInstance();
$approving_user = 7449;
$commit = true;

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

function getTagsForSeason($season_id) {
  //Gets a list of tags for a "Season"
  global $db;
  return $db->fetch_column('SELECT tag FROM sched_showtag WHERE entryid=$1', array($season_id));
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
    $show_meta = array('created' => strtotime('+10 Years'), 'tags' => array(), 'presenters' => array());
    $previousshow = $shows[$i]['summary'];
    $season_number = 1;
    echo '<div style="background-color:#ccc">New Show: '.$previousshow.'</div><details>';
  }
  
  //Continue with the current show, adding the new season
  echo 'Season '.$season_number.'<br><details style="margin-left:20px">';
  
  if ($show_meta['created'] > strtotime($shows[$i]['createddate'])) {
    $show_meta['created'] = strtotime($shows[$i]['createddate']);
  }
  //Add tags
  foreach (getTagsForSeason($shows[$i]['entryid']) as $tag) {
    $tag = strtolower($tag);
    if (!in_array($tag, $show_meta['tags'])) $show_meta['tags'][] = $tag;
  }
  
  $season = array(
      'timeslots' => getTimeslotsForSeason($shows[$i]['entryid']),
      'description' => $shows[$i]['description'],
      'submitted' => strtotime($shows[$i]['createddate'])
      );
  //Figure out presenter changes
  $presenter_start_time = strtotime($season['timeslots'][0]['starttime'])-1;
  //If it's the last show, it's effective to present date
  if (!isset($shows[$i+1]) or $shows[$i+1]['summary'] !== $previousshow) {
    $presenter_end_time = null;
  } else {
    $presenter_end_time = strtotime($season['timeslots'][sizeof($season['timeslots'])-1]['starttime'])+$season['timeslots'][sizeof($season['timeslots'])-1]['duration'];
  }
  foreach (getPresentersForSeason($shows[$i]['entryid']) as $presenter) {
    //If it's a new presenter, add them
    if (!isset($show_meta['presenters'][$presenter])) {
      $show_meta['presenters'][$presenter] = array(
          'effective_from' => $presenter_start_time,
          'effective_to' => $presenter_end_time
          );
    } else {
      //Update an existing end time
      if ($show_meta['presenters'][$presenter]['effective_to'] < $presenter_end_time or $presenter_end_time === null) {
        $show_meta['presenters'][$presenter]['effective_to'] = $presenter_end_time;
      }
    }
  }
  echo nl2br(print_r($season, true));
  $seasons[] = $season;
  echo '</details>';
  $season_number++;
}

echo '<details>'.nl2br(print_r($show_seasoned, true)).'</details>';


if ($commit) {
  //Reset
  $db->query('DELETE FROM schedule.show');


  //Insert the new shows
  foreach ($show_seasoned as $name => $show) {
    $owner = array_keys($show['info']['presenters']);
    $owner = $owner[0];
    $submitted = timeToTimestamp($show['info']['created']);
    $result = $db->fetch_column('INSERT INTO schedule.show (show_type_id, submitted, memberid) VALUES (1, $1, $2) RETURNING show_id',
            array($submitted, $owner));
    $show_id = $result[0];

    //Add name
    $db->query('INSERT INTO schedule.show_metadata (metadata_key_id, show_id, metadata_value, effective_from, memberid, approvedid) VALUES
      (2, $1, $2, \'1970-01-01 00:00:00+00\', $3, $4)',
            array($show_id, $name, $owner, $approving_user));
    //Add description
    $db->query('INSERT INTO schedule.show_metadata (metadata_key_id, show_id, metadata_value, effective_from, memberid, approvedid) VALUES
      (1, $1, $2, \'1970-01-01 00:00:00+00\', $3, $4)',
            array($show_id, $show[0]['description'], $owner, $approving_user));
    //Add tags
    foreach ($show['info']['tags'] as $tag) {
      $db->query('INSERT INTO schedule.show_metadata (metadata_key_id, show_id, metadata_value, effective_from, memberid, approvedid) VALUES
      (4, $1, $2, \'1970-01-01 00:00:00+00\', $3, $4)',
            array($show_id, $tag, $owner, $approving_user));
    }
    //Add presenters
    foreach ($show['info']['presenters'] as $presenter => $pinfo) {
      $db->query('INSERT INTO schedule.show_credit (show_id, credit_type_id, creditid, effective_from, effective_to, memberid, approvedid) VALUES
      ($1, 1, $2, $3, $4, $5, $6)',
            array($show_id, $presenter, timeToTimestamp($pinfo['effective_from']), timeToTimestamp($pinfo['effective_to']), $owner, $approving_user));
    }
    
    /**
     * Add Seasons
     */
    foreach ($show as $key => $season) {
      if ($key === 'info') continue;
      $season_id = $db->fetch_column('INSERT INTO schedule.show_season (show_id, termid, submitted, memberid) VALUES
        ($1, (SELECT termid FROM public.terms WHERE start < $2 ORDER BY start ASC LIMIT 1), $3, $4) RETURNING show_season_id',
              array($show_id, $season['timeslots'][0]['starttime'], timeToTimestamp($season['submitted']), $owner));
      $season_id = $season_id[0];
      //Add description
      $db->query('INSERT INTO schedule.season_metadata (metadata_key_id, show_season_id, metadata_value, effective_from, memberid, approvedid)
        VALUES (1, $1, $2, \'1970-01-01 00:00:00+00\', $3, $4)',
              array($season_id, $season['description'], $owner, $approving_user));
      
      //Add Timeslots
      foreach ($season['timeslots'] as $timeslot) {
        //$db->query('INSERT INTO schedule.') Table doesn't exist yet
      }
    }
  }

}

echo '</div>';

$twig = CoreUtils::getTemplateObject();
$twig->addVariable('serviceName', 'Configuration')
     ->addVariable('serviceVersion', 'Experimental')
     ->setTemplate('stripe.twig')
     ->addVariable('title', $module)
     ->addVariable('stripedata', ob_get_clean())
     ->render();