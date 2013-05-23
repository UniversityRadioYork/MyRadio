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
 * @package MyURY_NIPSWeb
 */
class NIPSWeb_BAPSUtils extends ServiceAPI {

  public static function getBAPSShowIDFromTimeslot(MyURY_Timeslot $timeslot) {

    $result = self::$db->fetch_column('SELECT showid FROM baps_show
      WHERE externallinkid=$1 LIMIT 1', array($timeslot->getID()));

    if (empty($result)) {
      //No match. Create a show
      $result = self::$db->fetch_column('INSERT INTO baps_show
        (userid, name, broadcastdate, externallinkid, viewable)
        VALUES (4, $1, $2, $3, true) RETURNING showid',
              array($timeslot->getName() . '-' . $timeslot->getID(), $timeslot->getStartTime(), $timeslot->getID()));
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

  public static function saveListingsForTimeslot(MyURY_Timeslot $timeslot) {
    //Get the timeslot's show plan
    $tracks = $timeslot->getShowPlan();

    //Get the listings related to the show
    $showid = self::getBAPSShowIDFromTimeslot($timeslot);
    $listings = self::getListingsForShow($showid);

    //Start a transaction for this change
    self::$db->query('BEGIN');

    foreach ($listings as $listing) {
      //Delete the old format
      self::$db->query('DELETE FROM baps_item WHERE listingid=$1', array($listing['listingid']), true);
      //Add each new entry
      $position = 1;
      foreach ($tracks[$listing['channel']] as $track) {
        switch ($track['type']) {
          case 'central':
            $file = self::getTrackDetails($track['trackid'], $track['album']['recordid']);
            self::$db->query('INSERT INTO baps_item (listingid, position, libraryitemid, name1, name2)
              VALUES ($1, $2, $3, $4, $5)', array(
                $listing['listingid'],
                $position,
                $file['libraryitemid'],
                $file['title'],
                $file['artist']
                    ), true);
            
            break;
          case 'aux':
            //Get the LegacyDB ID of the file
            $fileitemid = self::getFileItemFromManagedID($track['managedid']);
            self::$db->query('INSERT INTO baps_item (listingid, position, fileitemid, name1)
              VALUES ($1, $2, $3, $4)', array(
                (int) $listing['listingid'],
                (int) $position,
                (int) $fileitemid,
                $track['title']
                    ), true);
            break;
          default:
            throw new MyURYException('What do I even with this item?');
        }
        $position++;
      }
    }

    self::$db->query('COMMIT');
  }

  /**
   * Gets the title, artist, and BapsWeb libraryitemid of a track
   * @param int $trackid The Track ID from the rec database
   * @param int $recordid The Record ID from the rec database
   * @return boolean|array False on failure, or an array of the above
   */
  private static function getTrackDetails($trackid, $recordid) {
    $trackid = (int) $trackid;
    $recordid = (int) $recordid;
    $result = self::$db->fetch_one('SELECT title, artist, libraryitemid
      FROM rec_track, baps_libraryitem
      WHERE rec_track.trackid = baps_libraryitem.trackid
      AND rec_track.trackid=$1 LIMIT 1', array($trackid));

    if (empty($result)) {
      //Create the baps_libraryitem and recurse. pg_query_params doesn't like this...
      $result = self::$db->query('INSERT INTO baps_libraryitem
        (trackid, recordid) VALUES ($1, $2)', array($trackid, $recordid));
      return self::getTrackDetails($trackid, $recordid);
    }

    return $result;
  }

  /**
   * Returns the FileItemID from a ManagedItemID
   */
  public static function getFileItemFromManagedID($auxid) {
    $fileinfo = self::getManagedFromItem($auxid);

    $legacy_path = Config::$music_smb_path . '\\membersmusic\\fileitems\\' . self::sanitisePath($fileinfo['title']) . '_' . $auxid . '.mp3';
    //Make a hard link if it doesn't exist
    $ln_path = Config::$music_central_db_path . '/membersmusic/fileitems/' . self::sanitisePath($fileinfo['title']) . '_' . $auxid . '.mp3';
    if (!file_exists($ln_path)) {
      if (!@link(Config::$music_central_db_path . '/membersmusic/' . $fileinfo['folder'] . '/' . $auxid . '.mp3', $ln_path)) {
        trigger_error(Config::$music_central_db_path . '/membersmusic/' . $fileinfo['folder'] . '/' . $auxid . '.mp3' . ' to ' . $ln_path);
      }
    }
    $id = self::getFileItemFromPath($legacy_path);

    if (!$id) {
      //Create it
      $r = self::$db->fetch_column('INSERT INTO public.baps_fileitem (filename) VALUES ($1) RETURNING fileitemid', array($legacy_path));
      return $r[0];
    }
    return $id;
  }

  /**
   * Returns the ID of an item in the auxillary database based on its samba path
   * @param string $path The Samba Share location of the file to search for
   * @return boolean|int false on error or non existent, fileitemid otherwise
   */
  public static function getFileItemFromPath($path) {
    $result = self::$db->fetch_column('SELECT fileitemid FROM baps_fileitem
      WHERE filename=$1 LIMIT 1', array($path));
    if (empty($result))
      return false;

    return (int) $result[0];
  }

  /**
   * Ensure a string can be used as a filename
   * @param type $file
   */
  public static function sanitisePath($file) {
    return trim(preg_replace("/[^0-9^a-z^,^_^.^\(^\)^-^ ]/i", "", str_replace('..', '.', $file)));
  }

  private static function getManagedFromItem($managedid) {
    return self::$db->fetch_one('
      SELECT manageditemid, title, length, folder FROM bapsplanner.managed_items, bapsplanner.managed_playlists
        WHERE managed_items.managedplaylistid=managed_playlists.managedplaylistid AND manageditemid=$1
      UNION
        SELECT manageditemid, title, length, managedplaylistid AS folder FROM bapsplanner.managed_user_items WHERE manageditemid=$1
      LIMIT 1'
                    , array($managedid));
  }

}
