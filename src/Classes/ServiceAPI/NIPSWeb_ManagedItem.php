<?php
/**
 * This file provides the NIPSWeb_ManagedItem class for MyURY - these are Jingles, Beds, Adverts and others of a similar
 * ilk
 * @package MyURY_NIPSWeb
 */

/**
 * The NIPSWeb_ManagedItem class helps provide control and access to Beds and Jingles and similar not-PPL resources
 * 
 * @version 13032013
 * @author Lloyd Wallis <lpw@ury.org.uk>
 * @package MyURY_NIPSWeb
 * @uses \Database
 */
class NIPSWeb_ManagedItem extends ServiceAPI {
  /**
   * The Singleton store for ManagedItem objects
   * @var MyURY_Track
   */
  private static $resources = array();
  
  private $managed_item_id;
  
  private $managed_playlist;
  
  private $folder;
  
  private $title;
  
  private $length;
  
  private $bpm;
  
  private $expirydate;
  
  private $member;
  
  /**
   * Initiates the ManagedItem variables
   * @param int $resid The ID of the managed resource to initialise
   * @param NIPSWeb_ManagedPlaylist $playlistref If the playlist is requesting this item, then pass the playlist object
   * @todo Length, BPM
   * @todo Seperate Managed Items and Managed User Items. The way they were implemented was a horrible hack, for which
   * I am to blame. I should go to hell for it, seriously - Lloyd
   */
  private function __construct($resid, $playlistref = null) {
    $this->managed_item_id = $resid;
    //*dies*
    $result = self::$db->fetch_one('SELECT manageditemid, title, length, bpm, NULL AS folder, memberid, expirydate,
        managedplaylistid
        FROM bapsplanner.managed_items WHERE manageditemid=$1
      UNION SELECT manageditemid, title, length, bpm, managedplaylistid AS folder, NULL AS memberid, NULL AS expirydate,
        NULL as managedplaylistid
        FROM bapsplanner.managed_user_items WHERE manageditemid=$1
      LIMIT 1',
            array($resid));
    if (empty($result)) {
      throw new MyURYException('The specified NIPSWeb Managed Item or Managed User Item does not seem to exist');
      return;
    }
    
    $this->managed_playlist = empty($result['managedplaylistid']) ? null : 
            (($playlistref instanceof NIPSWeb_ManagedPlaylist) ? $playlistref :
            NIPSWeb_ManagedPlaylist::getInstance($result['managedplaylistid']));
    $this->folder = $result['folder'];
    $this->title = $result['title'];
    $this->length = strtotime('1970-01-01 '.$result['length']);
    $this->bpm = (int)$result['bpm'];
    $this->expirydate = strtotime($result['expirydate']);
    $this->member = empty($result['memberid']) ? null : User::getInstance($result['memberid']);
  }
  
  /**
   * Returns the current instance of that ManagedItem object if there is one, or runs the constructor if there isn't
   * @param int $resid The ID of the ManagedItem to return an object for
   * @param NIPSWeb_ManagedPlaylist $playlistref If the playlist is requesting this item, then pass the playlist object
   * itself as a reference to prevent cyclic dependencies.
   */
  public static function getInstance($resid = -1, $playlistref = null) {
    self::__wakeup();
    if (!is_numeric($resid)) {
      throw new MyURYException('Invalid ManagedResourceID!');
    }

    if (!isset(self::$resources[$resid])) {
      self::$resources[$resid] = new self($resid, $playlistref);
    }

    return self::$resources[$resid];
  }
  
  /**
   * Get the Title of the ManagedItem
   * @return String
   */
  public function getTitle() {
    return $this->title;
  }
  
  /**
   * Get the unique manageditemid of the ManagedItem
   * @return int
   */
  public function getID() {
    return $this->managed_item_id;
  }
  
  /**
   * Get the length of the ManagedItem, in seconds
   * @todo Not Implemented as Length not stored in DB
   * @return int
   */
  public function getLength() {
    return $this->length;
  }
  
  /**
   * Get the path of the ManagedItem
   * @return string
   */
  public function getPath() {
    return Config::$music_central_db_path.'/'.($this->managed_playlist ? $this->managed_playlist->getFolder() : $this->folder).'/'.$this->getID().'.mp3';
  }

  public function getPlaylist() {
    return NIPSWeb_ManagedPlaylist::getInstance($this->$managed_playlist);
  }
  public function getFolder() {
    $dir = Config::$music_central_db_path.$this->folder;
    if (!is_dir($dir)) mkdir($dir);
    return $dir;
  }
  
  /**
   * Returns an array of key information, useful for Twig rendering and JSON requests
   * @todo Expand the information this returns
   * @return Array
   */
  public function toDataSource() {
    return array(
        'type' => 'aux', //Legacy NIPSWeb Views
        'summary' => $this->getTitle(), //Again, freaking NIPSWeb
        'title' => $this->getTitle(),
        'managedid' => $this->getID(),
        'length' => CoreUtils::happyTime($this->getLength() > 0 ? $this->getLength() : 0, true, false),
        'trackid' => $this->getID(),
        'recordid' => 'ManagedDB', //Legacy NIPSWeb Views
        'auxid' => 'managed:' . $this->getID() //Legacy NIPSWeb Views
    );
  }

  public function cacheItem($tmp_path) {
    if (!isset($_SESSION['myury_nipsweb_file_cache_counter'])) $_SESSION['myury_nipsweb_file_cache_counter'] = 0;
    if (!is_dir(Config::$audio_upload_tmp_dir)) {
      mkdir(Config::$audio_upload_tmp_dir);
    }
    
    $filename = session_id() . '-' . ++$_SESSION['myury_nipsweb_file_cache_counter'] . '.mp3';
    
    move_uploaded_file($tmp_path, Config::$audio_upload_tmp_dir . '/' . $filename);
    
    $getID3 = new getID3;
    $fileInfo = $getID3->analyze(Config::$audio_upload_tmp_dir . '/' . $filename);

    $_SESSION['uploadInfo'][$filename] = $fileInfo;

    // File quality checks
    if ($fileInfo['audio']['bitrate'] < 192000) {
      return array('status' => 'FAIL', 'error' => 'Bitrate is below 192kbps.', 'fileid' => $filename, 'bitrate' => $fileInfo['audio']['bitrate']);
    }
    if (strpos($fileInfo['audio']['channelmode'], 'stereo') === false) {
      return array('status' => 'FAIL', 'error' => 'Item is not stereo.', 'fileid' => $filename, 'channelmode' => $fileInfo['audio']['channelmode']);
    }

    return array(
        'fileid' => $filename,
    );
  }

  public function storeItem($tmpid, $title) {

    $options = array(
      'title' => $title,
      'expires' => $_REQUEST['expires'],
      'auxid' => $_REQUEST['auxid'],
      'duration' => $_SESSION['uploadInfo'][$tmpid]['playtime_seconds'],
      );

    $item = self::create($options);

    if (!$item) {
      //Database transaction failed.
      return array('status' => 'FAIL', 'error' => 'A database kerfuffle occured.', 'fileid' => $_REQUEST['fileid']);
    }

    $folder = $item->getFolder();
    if(is_null($folder)) {
      $folder = $item->getPlaylist()->getFolder();
    }

    /**
     * Store three versions of the track:
     * 1- 192kbps MP3 for BAPS and Chrome/IE
     * 2- 192kbps OGG for Safari/Firefox
     * 3- Original file for potential future conversions
     */
    $tmpfile = Config::$audio_upload_tmp_dir.'/'.$tmpid;
    $dbfile = $folder.'/'.$item->getID();

    //Convert it with ffmpeg
    shell_exec("nice -n 15 ffmpeg -i '$filename' -ab 192k -f mp3 - >'{$dbfile}.mp3'");
    shell_exec("nice -n 15 ffmpeg -i '$filename' -acodec libvorbis -ab 192k '{$dbfile}.ogg'");
    rename($filename, $dbfile.'.'.$_SESSION['uploadInfo'][$tmpid]['fileformat'].'.orig');

    if (!file_exists($dbfile.'.mp3') || !file_exists($dbfile.'.ogg')) {
      //Conversion failed!
      return array('status' => 'FAIL', 'error' => 'Conversion with ffmpeg failed.', 'fileid' => $_REQUEST['fileid']);
    }
    elseif (!file_exists($dbfile.'.'.$_SESSION['uploadInfo'][$tmpid]['fileformat'].'.orig')) {
      return array('status' => 'FAIL', 'error' => 'Could not move file to library.', 'fileid' => $_REQUEST['fileid']);
    }

    return array('status' => 'OK');
  }

  /**
   * Create a new NIPSWEB_ManagedItem with the provided options
   * @param Array $options
   * title (required): Title of the item.
   * duration (required): Duration of the item, in seconds
   * auxid (required): The auxid of the playlist
   * bpm: The beats per minute of the item
   * expires: The expiry date of the item
   * @return NIPSWEB_ManagedItem a shiny new NIPSWEB_ManagedItem with the provided options
   * @throws MyURYException
   */
  public static function create($options) {
    self::__wakeup();
    
    $required = array('title', 'duration', 'auxid');
    foreach ($required as $require) {
      if (empty($options[$require])) throw new MyURYException($require.' is required to create an Item.', 400);
    }
    //BPM null
    if (empty($options['bpm'])) $options['bpm'] = null;
    //Expires null
    if (empty($options['expires'])) $options['expires'] = null;
    
    //Decode the auxid to figure out what/where we're adding
    if (strpos($options['auxid'], 'user-') !== false) {
      //This is a personal resource
      $path = str_replace('user-', '/membersmusic/', $options['auxid']);
      $result = self::$db->query('INSERT INTO bapsplanner.managed_user_items (managedplaylistid, title, length, bpm)
       VALUES ($1, $2, $3, $4) RETURNING manageditemid',
               array(
                    $path,
                    trim($options['title']),
                    CoreUtils::intToTime($options['duration']),
                    $options['bpm'],
                ));      
    }
    else {
      //This is a central resource
      $result = self::$db->fetch_one('SELECT managedplaylistid FROM bapsplanner.managed_playlists WHERE folder=$1 LIMIT 1', array(str_replace('aux-', '', $options['auxid'])));
        if (empty($result))
          return false;
        $playlistid = $result[0];

      $result = self::$db->query('INSERT INTO bapsplanner.managed_items (managedplaylistid, title, length, bpm, expirydate, memberid)
       VALUES ($1, $2, $3, $4, $5, $6) RETURNING manageditemid',
              array(
                  $playlistid,
                  trim($options['title']),
                  CoreUtils::intToTime($options['duration']),
                  $options['bpm'],
                  $options['expires'],
                  $_SESSION['memberid'],
              ));
    }
    
    $id = self::$db->fetch_all($result);
    
    return self::getInstance($id[0]['manageditemid']);
  }
}
