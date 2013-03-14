<?php

/*
 * Provides the Album class for MyURY
 * Based and bashed around from the Artist class, so if there was a problem with that (12/Aug/2012), then it's likely to be here as well
 * @package MyURY_Core
 */

/*
 * The Album class fetches information about albums in the Cental Databse. It may be expanded to deal with entering and modifying them at some point as well.
 * @version 12082012
 * @author Anthony Williams <anthony@ury.york.ac.uk>
 * @todo EVERYTHING
 * @package MyURY_Core
 * @uses \Database
 * 
 */

class Album extends ServiceAPI {

  /**
   * The singleton store for Album objects
   * @var Album[]
   */
  private static $albums = array();
  
  /**
   * The Title of the release
   * @var String
   */
  private $title;
  
  private $artist;
  
  private $status;
  
  private $media;
  
  private $format;
  
  private $record_label;
  
  private $date_added;
  
  private $date_released;
  
  private $shelf_number;
  
  private $shelf_letter;
  
  private $albumid;
  
  private $member_add;
  
  private $member_edit;
  
  private $last_modified;
  
  private $cdid;
  
  private $location;
  
  private $tracks = array();

  private function __construct($recordid) {
    $this->albumid = $recordid;
    
    $result = self::$db->fetch_one('SELECT * FROM (SELECT * FROM public.rec_record WHERE recordid=$1 LIMIT 1) AS t1
      LEFT JOIN public.rec_statuslookup ON t1.status = rec_statuslookup.status_code
      LEFT JOIN public.rec_medialookup ON t1.media = rec_medialookup.media_code
      LEFT JOIN public.rec_formatlookup ON t1.format = rec_formatlookup.format_code
      LEFT JOIN public.rec_locationlookup ON t1.location = rec_locationlookup.location_code', array($recordid));
    
    $this->title = $result['title'];
    $this->artist = $result['artist'];
    $this->status = $result['status_descr'];
    $this->media = $result['media_descr'];
    $this->format = $result['format_descr'];
    $this->record_label = $result['recordlabel'];
    $this->date_added = strtotime($result['dateadded']);
    $this->date_released = strtotime($result['datereleased']);
    $this->shelf_number = (int)$result['shelfnumber'];
    $this->shelf_letter = $result['shelfletter'];
    $this->member_add = empty($result['memberid_add']) ? null : User::getInstance($result['memberid_add']);
    $this->member_edit = empty($result['memberid_edit']) ? null : User::getInstance($result['memberid_edit']);
    $this->last_modified = strtotime($result['datetime_lastedit']);
    $this->cdid = $result['cdid'];
    $this->location = $result['location_descr'];
    
    if (empty($result)) {
      throw new MyURYException('The specified Record/Album does not seem to exist');
      return;
    }
  }

  public static function getInstance($recordid = -1) {
    self::__wakeup();
    if (!is_numeric($recordid)) {
      throw new MyURYException('Invalid Record/Album ID!', MyURYException::FATAL);
    }

    if (!isset(self::$albums[$recordid])) {
      self::$albums[$recordid] = new self($recordid);
    }

    return self::$albums[$recordid];
  }
  
  public function getID() {
    return $this->albumid;
  }
  
  public function getTitle() {
    return $this->title;
  }

  public static function findByName($title, $limit) {
    $title = trim($title);
    return self::$db->fetch_all('SELECT DISTINCT rec_record.recordid AS recordid, FROM rec_record WHERE rec_record.title ILIKE \'%\' || $1 || \'%\' LIMIT $2;', array($title, $limit));
  }

  public static function getAlbumDetails($title) {
    $title = trim($title);
    return self::$db->fetch_all('SELECT DISTINCT rec_record.title AS title, rec_record.artist AS artist, rec_record.status AS status FROM rec_record WHERE rec_record.recordid = $1;', array($title, $limit));
  }
  
  public function toDataSource() {
    return array(
      'title' => $this->getTitle(),
      'recordid' => $this->getID()
    );
  }

}