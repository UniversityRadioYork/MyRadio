<?php

die("Disabled as Scheduler Migration has now been finalised and this tool should no longer be used.");
/**
 * Script to migrate SIS Signins and Messages from the old schema to Scheduler 2.0
 */
if (!isset($_POST['confirm'])) {
  ?>
<h1>Schedule State Migration Tool (SSMT) Phase 2</h1>
<form action="?module=Scheduler&action=upgradeRefs" method="post">
  <input type="hidden" name="confirm" />
  <input type="submit" value="I UNDERSTAND THIS WILL RESET THE NEW SIS COMM AND SIGNIN SCHEMA AND MUST NOE BE USED IN PRODUCTION" />
</form>
<?php
exit;
}
//ob_start();
echo '<div class="ui-state-error">This script deletes all data from the new SIS Messages and Signin schemata.</div>';
error_reporting(E_ALL);
ini_set('display_errors', 'On');
$db = Database::getInstance();

$scache = array();
function sch_getNewIDFromOld($oldid) {
  if ($oldid > 50000) {
    echo 'New message.<br>';
    return $oldid; //The temporary SIS hack did this
  }
  global $scache;
  if (isset($scache[$oldid])) return $scache[$oldid];
  global $db;
  $r = pg_fetch_assoc(pg_query_params('SELECT show_season_timeslot_id FROM schedule.show_season_timeslot
    WHERE start_time >= (SELECT starttime FROM sched_timeslot WHERE timeslotid=$1) ORDER BY start_time ASC LIMIT 1', array($oldid)));
  if (empty($r) or $r['show_season_timeslot_id'] == 0) {
    die("<br><strong>$oldid WAS FATAL</strong>");
  }
  $scache[$oldid] = $r['show_season_timeslot_id'];
  
  return $r['show_season_timeslot_id'];
}

echo 'Upgrading SIS Signin<br>';
pg_query('DELETE FROM sis2.member_signin');
$r = pg_query('SELECT * FROM public.sis_sign');
while ($s = pg_fetch_assoc($r)) {
  if (!pg_query_params('INSERT INTO sis2.member_signin (memberid, sign_time, signerid, show_season_timeslot_id)
    VALUES ($1, $2, $3, $4)', array(
       $s['memberid'], $s['actiontime'], $s['signedmemberid'], sch_getNewIDFromOld($s['timeslotid']) 
    ))) {
    echo '<strong>'.pg_last_error().'</strong><br>';
    }
}

echo 'Upgrading Messages<br>';
pg_query('DELETE FROM sis2.messages');
$r = pg_query('SELECT * FROM public.sis_comm WHERE timeslotid IS NOT NULL');

while ($m = pg_fetch_assoc($r)) {
  if ($m['timeslotid'] == null) {
    //Dump null reference messages
    continue;
  }
  $newid = sch_getNewIDFromOld($m['timeslotid']);
  //Insert the message to the new table
  if (!pg_query_params('INSERT INTO sis2.messages (timeslotid, commtypeid, sender, date, subject, content, statusid, comm_source)
    VALUES ($1, $2, $3, $4, $5, $6, $7, $8)', array(
       $newid, $m['commtypeid'], $m['sender'], $m['date'], $m['subject'], $m['content'], $m['statusid'], $m['comm_source'] 
    ))) {
    echo '<strong>'.pg_last_error().'</strong>';
    }
}

echo 'Upgrade Complete.<br>';

//Iterate over ever message ever (don't do this in ram like stage one. There's a few of them.
exit;
$twig = CoreUtils::getTemplateObject();
$twig->addVariable('serviceName', 'Configuration')
     ->addVariable('serviceVersion', 'Experimental')
     ->setTemplate('stripe.twig')
     ->addVariable('title', 'Schedule State Migration Tool')
     ->addVariable('stripedata', ob_get_clean())
     ->render();