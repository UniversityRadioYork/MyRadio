<?php

class MyURY_BAPSSyncDaemon {
  public static function isEnabled() { return false; }
  
  /**
   * Takes each of the NIPSWebs Managed and Resource Lists, and converts them into BAPS Recommended Audio shows
   */
  public static function run() {
    $special_date = '2034-05-06 07:08:09'; //All shows created by this are identified by this time.
    $db = Database::getInstance();
    
    //This needs to appear atomic to end users
    $db->query('BEGIN');
    $db->query('DELETE FROM public.baps_show WHERE broadcastdate=$1', array($special_date));
    //Start with the Jukebox Playlists
    foreach (iTones_Playlist::getAlliTonesPlaylists() as $list) {
      /**
       * Create the playlist. '61' is system, which appears to be how BAPS chooses what shows are recommended listening
       */
      $r = $db->fetch_column('INSERT INTO public.baps_show (userid, name, broadcastdate, viewable)
        VALUES (61, $1, $2, true) RETURNING showid', array($list->getTitle(), $special_date));
      $showid = $r[0];
      
      if (empty($r))
        return;

      /**
       * Create a single channel for the show, containing the items
       */
      $r = $db->fetch_one('INSERT INTO public.baps_listing (showid, name, channel) VALUES
        ($1, $2, 0) RETURNING listingid', array($showid, $list->getTitle()));
      $listingid = $r[0];
      
      if (empty($r))
        return;

      $i = 0;
      foreach ($this->getManagedPlaylist($list['playlistid']) as $item) {
        $track = $this->getTrackDetails($item['trackid'], $item['recordid']);
        $i++;
        pg_query_params('INSERT INTO public.baps_item (listingid, name1, name2, position, libraryitemid)
          VALUES ($1, $2, $3, $4, $5)', array($listingid, $item['title'], $item['artist'], $i, $track['libraryitemid']));
        echo pg_last_error();
      }
    }

    //And now the Aux Resource Playlists
    foreach ($this->getCentralResourceLists() as $list) {
      /**
       * Create the playlist. '61' is system, which appears to be how BAPS chooses what shows are recommended listening
       */
      $r = pg_fetch_row(pg_query_params('INSERT INTO public.baps_show (userid, name, broadcastdate, viewable)
        VALUES (61, \'Managed::\'||$1, $2, true) RETURNING showid', array($list['name'], $special_date)));
      $showid = $r[0];
      echo pg_last_error();
      if (!$r)
        return;

      /**
       * Create a single channel for the show, containing the items
       */
      $r = pg_fetch_row(pg_query_params('INSERT INTO public.baps_listing (showid, name, channel) VALUES
        ($1, $2, 0) RETURNING listingid', array($showid, $list['name'])));
      $listingid = $r[0];
      echo pg_last_error();
      if (!$r)
        return;

      $i = 0;
      foreach ($this->getAuxItems($list['folder']) as $item) {
        $track = $this->getFileItemFromManagedID($item['manageditemid']);
        $i++;
        pg_query_params('INSERT INTO public.baps_item (listingid, name1, name2, position, fileitemid)
          VALUES ($1, $2, $3, $4, $5)', array($listingid, $item['title'], '', $i, $track));
        echo pg_last_error();
      }
    }

    echo pg_last_error();
    pg_query('COMMIT');
  }
}