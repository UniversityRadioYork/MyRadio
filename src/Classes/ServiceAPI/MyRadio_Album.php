<?php
/**
 * Provides the MyRadio_Album class for MyRadio
 * @package MyRadio_Core
 */

/**
 * The Album class fetches information about albums in the Cental Database.
 * @version 20130803
 * @author Anthony Williams <anthony@ury.york.ac.uk>
 * @author Lloyd Wallis <lpw@ury.org.uk>
 * @package MyRadio_Core
 * @uses \Database
 * 
 */

class MyRadio_Album extends ServiceAPI {
  
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
  
  private $label;
  
  private $tracks = array();

  protected function __construct($recordid) {
    $this->albumid = $recordid;
    
    $result = self::$db->fetch_one('SELECT * FROM (SELECT * FROM public.rec_record WHERE recordid=$1 LIMIT 1) AS t1
      LEFT JOIN public.rec_statuslookup ON t1.status = rec_statuslookup.status_code
      LEFT JOIN public.rec_medialookup ON t1.media = rec_medialookup.media_code
      LEFT JOIN public.rec_formatlookup ON t1.format = rec_formatlookup.format_code
      LEFT JOIN public.rec_locationlookup ON t1.location = rec_locationlookup.location_code', array($recordid));
    
    if (empty($result)) {
      throw new MyRadioException('The specified Record/Album does not seem to exist');
      return;
    }
    
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
    $this->member_add = empty($result['memberid_add']) ? null : (int)$result['memberid_add'];
    $this->member_edit = empty($result['memberid_edit']) ? null : (int)$result['memberid_edit'];
    $this->last_modified = strtotime($result['datetime_lastedit']);
    $this->cdid = $result['cdid'];
    $this->location = $result['location_descr'];
    
    $this->tracks = self::$db->fetch_column('SELECT trackid FROM rec_track WHERE recordid=$1', array($this->albumid));
  }
  
  public function getID() {
    return $this->albumid;
  }
  
  public function getTracks() {
    return MyRadio_Track::resultSetToObjArray($this->tracks);
  }
  
  public function getTitle() {
    return $this->title;
  }
  
  public function getFolder() {
    $dir = Config::$music_central_db_path.'/records/'.$this->getID();
    if (!is_dir($dir)) {
      mkdir($dir);
    }
    return $dir;
  }
  
  /**
   * 
   * @param String $paramName The key to update, e.g. title.
   * Don't be silly and try to set recordid. Bad things will happen.
   * @param mixed $value The value to set the param to. Type depends on $paramName.
   */
  private function setCommonParam($paramName, $value) {
    /**
     * You won't believe how annoying psql can be about '' already being used on a unique key.
     */
    if ($value == '') $value = null;
    //Maps Class variable names to their database values, if they mismatch.
    $param_maps = ['albumid' => 'recordid'];

    if (!property_exists($this, $paramName))
      throw new MyRadioException('paramName invalid', 500);
    
    if ($this->$paramName == $value) return false;
    
    $this->$paramName = $value;

    if (isset($param_maps[$paramName]))
      $paramName = $param_maps[$paramName];

    self::$db->query('UPDATE public.rec_record SET ' . $paramName . '=$1 WHERE recordid=$2', array($value, $this->getID()));

    return true;
  }
  
  public function setTitle($title) {
    if (empty($title)) {
      throw new MyRadioException('Title must not be empty.', 400);
    }
    
    $this->setCommonParam('title', $title);
    
    return $this;
  }
  
  /**
   * Update the Artist for this Album
   * @param String $artist The Artist name
   * @param bool $applyToTracks If true, this will update the Artist for each individual Track in the Album.
   * Default false.
   * @return \MyRadio_Album
   * @throws MyRadioException
   */
  public function setArtist($artist, $applyToTracks = false) {
    if (empty($artist)) {
      throw new MyRadioException('Artist must not be empty.', 400);
    }
    
    $this->setCommonParam('artist', $artist);
    
    if ($applyToTracks) {
      foreach ($this->getTracks() as $track) {
        $track->setArtist($artist);
      }
    }
    
    return $this;
  }

  public static function findByName($title, $limit) {
    $title = trim($title);
    $result = self::$db->fetch_column('SELECT DISTINCT rec_record.recordid AS recordid FROM rec_record
      WHERE rec_record.title ILIKE \'%\' || $1 || \'%\' LIMIT $2;', array($title, $limit));
    
    $response = array();
    foreach ($result as $album) {
      $response[] = MyRadio_Album::getInstance($album);
    }
    
    return $response;
  }
  
  public static function findOrCreate($title, $artist) {
    $title = trim($title);
    $artist = trim($artist);
    
    $result = self::$db->fetch_one('SELECT recordid FROM rec_record WHERE title=$1 AND artist=$2 LIMIT 1',
            array($title, $artist));
    
    if (empty($result)) {
      //Create Album
      return self::create(array('title' => $title, 'artist' => $artist));
    } else {
      //Load Album
      return self::getInstance($result['recordid']);
    }
  }
  
  public static function create($options) {
    if (empty($options['title']) or empty($options['artist'])) {
      throw new MyRadioException('TITLE and ARTIST are required options to create an Album.', 400);
      return;
    }
    //Digitial Only
    if (!isset($options['status'])) $options['status'] = 'd';
    //NIPSWeb Upload
    if (!isset($options['media'])) $options['media'] = 'n';
    //Album
    if (!isset($options['format'])) $options['format'] = 'a';
    //Blank
    if (!isset($options['recordlabel'])) $options['recordlabel'] = '';
    //Shelf 0
    if (!isset($options['shelfnumber'])) $options['shelfnumber'] = 0;
    //Shelf a
    if (!isset($options['shelfletter'])) $options['shelfletter'] = 'a';
    //NULL CDID
    if (!isset($options['cdid'])) $options['cdid'] = null;
    //NULL location
    if (!isset($options['location'])) $options['location'] = null;
    //NULL promoter
    if (!isset($options['promoterid'])) $options['promoterid'] = null;
    
    $r = self::$db->query('INSERT INTO rec_record (title, artist, status, media, format, recordlabel, shelfnumber,
      shelfletter, memberid_add, cdid, location, promoterid) VALUES ($1, $2, $3, $4, $5, $6, $7, $8, $9, $10, $11, $12)
      RETURNING recordid', array(
          trim($options['title']),
          trim($options['artist']),
          $options['status'],
          $options['media'],
          $options['format'],
          $options['recordlabel'],
          $options['shelfnumber'],
          $options['shelfletter'],
          $_SESSION['memberid'],
          $options['cdid'],
          $options['location'],
          $options['promoterid']
      ));
    
    $id = self::$db->fetch_all($r);
    
    return self::getInstance($id[0]['recordid']);
  }
  
  public function toDataSource() {
    return array(
        'title' => $this->getTitle(),
        'recordid' => $this->getID(),
        'artist' => $this->artist,
        'cdid' => $this->cdid,
        'date_added' => CoreUtils::happyTime($this->date_added),
        'date_released' => CoreUtils::happyTime($this->date_released, false),
        'format' => $this->format,
        'last_modified' => CoreUtils::happyTime($this->last_modified),
        'location' => $this->location,
        'media' => $this->media,
        'member_add' => $this->member_add,
        'member_edit' => $this->member_edit,
        'record_label' => $this->record_label,
        'shelf_letter' => $this->shelf_letter,
        'shelf_number' => $this->shelf_number,
        'status' => $this->status,
        'label' => $this->record_label
    );
  }

}