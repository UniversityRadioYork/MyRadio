<?php
/*
 * This file provides the NIPSWeb_BAPSUtils class for MyURY
 * @package MyURY_NIPSWeb
 */

/**
 * This class has helper functions for saving Show Planner show informaiton into legacy BAPS Show layout
 * 
 * @version 20130508
 * @author Lloyd Wallis <lpw@ury.org.uk>
 */
class NIPSWeb_BAPSUtils extends ServiceAPI {
  
  public static function getBAPSShowIDFromTimeslot(MyURY_Timeslot $timeslot) {
    
    $result = self::$db->query('SELECT showid FROM baps_show
      WHERE externallinkid=$1 LIMIT 1', array($timeslot->getID()));

    if (!$result)
      return false;

    if (self::$db->num_rows($result) === 0) {
      //A show entry does not exist for this timeslot. Do an educated guess for
      // a legacy bapsplanner one
      $result = self::$db->fetch_column('SELECT showid FROM baps_show
        WHERE (userid IN (SELECT userid FROM baps_user_external
          WHERE externalid=$1 LIMIT 1) OR userid=4)
            AND broadcastdate < \'2012-04-30\'
            AND date_trunc(\'day\', broadcastdate)=$2
          LIMIT 1', array($_SESSION['memberid'], date('Y-m-d', $timeslot->getStartTime())));
      
      if (empty($result)) {
        //No match. Create a show
        $result = self::$db->fetch_column('INSERT INTO baps_show
        (userid, name, broadcastdate, externallinkid, viewable)
        VALUES (4, $1, $2, $3, true) RETURNING showid',
                array($timeslot->getName() . '-' . $timeslot->getID(), $timeslot->getStartTime(), $timeslot->getID()));
        
        return (int) $result[0];
      } else {
        //Seems legit. Update the database with this timeslot => show mapping
        $showid = (int) $result[0];
        self::$db->query('UPDATE baps_show SET externallinkid=$1
          WHERE showid=$2', array($timeslot->getID(), $showid));
        return $showid;
      }
    }
    
    return (int) $result[0];
  }
  
  /**
   * Takes a BAPS ShowID, and gets the channel references for the show
   * If a listing for one or more channels does not exist, this method
   * creates them automatically
   * @param int $showid The BAPS show id
   * @return boolean|Array An array of BAPS Channels, or false on failure
   */
  public static function getListingsForShow($showid) {
    $listings = self::$db->fetch_all('SELECT * FROM baps_listing
      WHERE showid=$1 ORDER BY channel ASC', array((int) $showid));
    
    if (!$listings)
      $listings = array();

    if (sizeof($listings) === 3) {
      //There's already three, there's no need to check which exist
      return $listings;
    }

    /**
     * @todo Wow I was lazy here.
     */
    $channels = array(false, false, false);

    //Flag existing channels as, well, existing
    foreach ($listings as $listing) {
      $channels[$listing['channel']] = true;
    }

    //Go over the channels and create the nonexistent ones
    $change = false;
    foreach ($channels as $channel => $exists) {
      if (!$exists) {
        $result = self::$db->query('INSERT INTO baps_listing (showid, name, channel)
          VALUES ($1, \'Channel ' . $channel . '\', $2)', array($showid, $channel));
        $change = (bool) $result;
      }
    }
    //If the show definition has changed, recurse this method
    if ($change)
      return $this->getListingsForShow($showid);
    else
      return $listings;
  }
  
}
