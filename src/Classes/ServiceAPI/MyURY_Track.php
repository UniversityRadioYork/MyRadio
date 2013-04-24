<?php
/**
 * This file provides the MyURY_Track class for MyURY
 * @package MyURY_Core
 */

/**
 * The MyURY_Track class provides and stores information about a Track
 * 
 * @version 06042013
 * @author Lloyd Wallis <lpw@ury.org.uk>
 * @package MyURY_Core
 * @uses \Database
 * @todo Cache this
 */
class MyURY_Track extends ServiceAPI {
  /**
   * The Singleton store for Track objects
   * @var MyURY_Track
   */
  private static $tracks = array();
  /**
   * The number of the Track on a Record
   * @var int
   */
  private $number;
  /**
   * The title of the Track
   * @var String
   */
  private $title;
  /**
   * The Artist of the Track
   * @var int
   */
  private $artist;
  /**
   * The length of the Track, in seconds
   * @var int
   */
  private $length;
  /**
   * The genreid of the Track
   * @var char
   */
  private $genre;
  /**
   * How long the intro (non-vocal) part of the track is, in seconds
   * @var int
   */
  private $intro;
  /**
   * Whether the track is clean:<br>
   * y: The track is verified as clean<br>
   * n: The track is verified as unclean<br>
   * u: This track has not been checked for cleanliness
   * @var String
   */
  private $clean;
  /**
   * The Unique ID of this Track
   * @var int
   */
  private $trackid;
  /**
   * The Record this track belongs to
   * @var int
   */
  private $record;
  /**
   * Whether or not there is a digital version of this track stored in the Central Database
   * @var bool
   */
  private $digitised;
  /**
   * The member who digitised this track
   * @var User
   */
  private $digitisedby;
  
  /**
   * Initiates the Track variables
   * @param int $trackid The ID of the track to initialise
   * @todo Genre class
   * @todo Artist normalisation
   */
  private function __construct($trackid, $album = null) {
    $this->trackid = $trackid;
    $result = self::$db->fetch_one('SELECT * FROM public.rec_track WHERE trackid=$1 LIMIT 1', array($trackid));
    if (empty($result)) {
      throw new MyURYException('The specified Track does not seem to exist');
      return;
    }
    
    $this->artist = $result['artist'];
    $this->clean = $result['clean'];
    $this->digitised = ($result['digitised'] == 't') ? true : false;
    $this->digitisedby = empty($result['digitisedby']) ? null : User::getInstance($result['digitisedby']);
    $this->genre = $result['genre'];
    $this->intro = strtotime('1970-01-01 '.$result['intro'].'+00');
    $this->length = strtotime('1970-01-01 '.$result['length'].'+00');
    $this->number = (int)$result['intro'];
    $this->record = empty($album) ? MyURY_Album::getInstance($result['recordid']) : $album;
    $this->title = $result['title'];
  }
  
  /**
   * Returns the current instance of that Track object if there is one, or runs the constructor if there isn't
   * @param int $trackid The ID of the Track to return an object for
   * @param MyURY_Album If defined, this is a reference to a preexisting album object. Prevents circular referncing.
   * 
   * @return MyURY_Track
   */
  public static function getInstance($trackid = -1, $album = null) {
    self::__wakeup();
    if (!is_numeric($trackid)) {
      throw new MyURYException('Invalid Track ID!', MyURYException::FATAL);
    }

    if (!isset(self::$tracks[$trackid])) {
      self::$tracks[$trackid] = new self($trackid, $album);
    }

    return self::$tracks[$trackid];
  }
  
  /**
   * Returns a "summary" string - the title and artist seperated with a dash.
   * @return String
   */
  public function getSummary() {
    return $this->getTitle() . ' - '.$this->getArtist();
  }
  
  /**
   * Get the Title of the Track
   * @return String
   */
  public function getTitle() {
    return $this->title;
  }
  
  /**
   * Get the Artist of the Track
   * @return String
   */
  public function getArtist() {
    return $this->artist;
  }
  
  /**
   * Get the Album of the Track;
   * @return Album
   */
  public function getAlbum() {
    return $this->record;
  }
  
  /**
   * Get the unique trackid of the Track
   * @return int
   */
  public function getID() {
    return $this->trackid;
  }
  
  /**
   * Get the length of the Track, in seconds
   * @return int
   */
  public function getLength() {
    return $this->length;
  }
  
  /**
   * Get whether or not the track is digitised
   * @return bool
   */
  public function getDigitised() {
    return $this->digitised;
  }
  
  /**
   * Update whether or not the track is digitised
   */
  public function setDigitised($digitised) {
    $this->digitised = $digitised;
    self::$db->query('UPDATE rec_track SET digitised=$1, digitisedby=$2 WHERE trackid=$3',
            $digitised ? array(
                't', $_SESSION['memberid'], $this->getID()
            ) : array(
                'f', null, $this->getID()
            )
            );
  }
  
  /**
   * Returns an array of key information, useful for Twig rendering and JSON requests
   * @todo Expand the information this returns
   * @return Array
   */
  public function toDataSource() {
    return array(
        'title' => $this->getTitle(),
        'artist' => $this->getArtist(),
        'type' => 'central', //Tells NIPSWeb Client what this item type is
        'album' => $this->getAlbum()->toDataSource(),
        'trackid' => $this->getID(),
        'length' => CoreUtils::happyTime($this->getLength(), true, false),
        'clean' => $this->clean === 'c',
        'digitised' => $this->getDigitised()
    );
  }
  
  /**
   * Returns an Array of Tracks matching the given partial title
   * @param String $title A partial or total title to search for
   * @param String $artist a partial or total title to search for
   * @param int $limit The maximum number of tracks to return
   * @param bool $digitised Whether the track must be digitised. Default false.
   * @param bool $exact Only return Exact matches (i.e. no %)
   * @return Array of Track objects
   */
  private static function findByNameArtist($title, $artist, $limit, $digitised = false, $exact = false) {
    $result = self::$db->fetch_column('SELECT trackid
      FROM rec_track, rec_record WHERE rec_track.recordid=rec_record.recordid
      AND rec_track.title '.($exact ? '=$1' : 'ILIKE \'%\' || $1 || \'%\'').
      'AND rec_track.artist '.($exact ? '=$2' : 'ILIKE \'%\' || $1 || \'%\'').
      ($digitised ? ' AND digitised=\'t\'' : '').'
      LIMIT $3',
            array($title, $artist, $limit));
    
    $response = array();
    foreach ($result as $trackid) {
      $response[] = new MyURY_Track($trackid);
    }
    
    return $response;
  }
  
  /**
   * 
   * @param Array $options One or more of the following:
   * title: String title of the track
   * artist: String artist name of the track
   * digitised: Boolean whether or not digitised
   * itonesplaylistid: Tracks that are members of the iTones_Playlist id
   * limit: Maximum number of items to return. 0 = No Limit
   * 
   * @todo Limit not accurate for itonesplaylistid queries
   */
  public static function findByOptions($options) {
    
    //Shortcircuit - if itonesplaylistid is the only not-default value, just return the playlist
    $conflict = false;
    foreach (array('title', 'artist', 'digitised') as $k) {
      if (!empty($options[$k])) {
        $conflict = true;
        break;
      }
    }
    
    if (!$conflict && !empty($options['itonesplaylistid']))
      return iTones_Playlist::getInstance($options['itonesplaylistid'])->getTracks();
    
    if (!isset($options['title'])) $options['title'] = '';
    if (!isset($options['artist'])) $options['artist'] = '';
    if (!isset($options['digitised'])) $options['digitised'] = true;
    if (!isset($options['itonesplaylistid'])) $options['itonesplaylistid'] = null;
    if (!isset($options['limit'])) $options['limit'] = Config::$ajax_limit_default;
    
    //Prepare paramaters
    $sql_params = array($options['title'], $options['artist']);
    if ($options['limit'] != 0) $sql_params[] = $options['limit'];
    
    //Do the bulk of the sorting with SQL
    $result = self::$db->fetch_column('SELECT trackid
      FROM rec_track, rec_record WHERE rec_track.recordid=rec_record.recordid
      AND rec_track.title ILIKE \'%\' || $1 || \'%\'
      AND rec_track.artist ILIKE \'%\' || $2 || \'%\'
      '.($options['digitised'] ? ' AND digitised=\'t\'' : '').'
      '.($options['limit'] == 0 ? '' : ' LIMIT $3'),
            $sql_params);
    
    $response = array();
    foreach ($result as $trackid) {
      $response[] = new MyURY_Track($trackid);
    }
    
    //Intersect with iTones if necessary, then return
    return empty($options['itonesplaylistid']) ? $response : array_intersect($response,
      iTones_Playlist::getInstance($options['itonesplaylistid'])->getTracks());
  }
  
  /**
   * This method processes an unknown mp3 file that has been uploaded, storing a temporary copy of the file in /tmp/,
   * then attempting to identify the track by querying it against the last.fm database.
   * 
   * @param type $tmp_path
   */
  public static function cacheAndIdentifyUploadedTrack($tmp_path) {
    if (!isset($_SESSION['myury_nipsweb_file_cache_counter'])) $_SESSION['myury_nipsweb_file_cache_counter'] = 0;
    if (!is_dir(Config::$audio_upload_tmp_dir)) {
      mkdir(Config::$audio_upload_tmp_dir);
    }
    
    $filename = session_id() . '-' . ++$_SESSION['myury_nipsweb_file_cache_counter'] . '.mp3';
    
    move_uploaded_file($tmp_path, Config::$audio_upload_tmp_dir . '/' . $filename);
    
    return array(
        'fileid' => $filename,
        'analysis' => self::identifyUploadedTrack(Config::$audio_upload_tmp_dir . '/' . $filename)
    );
  }
  
  /**
   * Attempts to identify an MP3 file against the last.fm database.
   * 
   * !This method requires the external lastfm-fpclient application to be installed on the server. A FreeBSD build
   * with URY's API key can be found in the fpclient.git URY Git repository.
   * 
   * @param String $path The location of the MP3 file
   * @return Array A parsed array version of the XML lastfm response
   */
  private static function identifyUploadedTrack($path) {
    $response = shell_exec('lastfm-fpclient '.$path);
    
    $lastfm = json_decode($response, true);
    
    if (empty($lastfm)) {
      return array('FAIL' => 'This track could not be identified. Please email the track to track.requests@ury.org.uk.');
    } else {
      $tracks = array();
      foreach ($lastfm['tracks']['track'] as $track) {
        $tracks[] = array('title' => $track['name'], 'artist' => $track['artist']['name']);
      }
      return $tracks;
    }
  }
  
  public static function identifyAndStoreTrack($tmpid, $title, $artist) {
    //Get the album info
    $ainfo = self::getAlbumDurationAndPositionFromLastfm($title, $artist);
    $track = self::findByNameArtist($title, $artist, 1, false, true);
    if (empty($track)) {
      //Create the track
      $track = self::create(array(
          'title' => $title,
          'artist' => $artist,
          'digitised' => true,
          'duration' => $ainfo['duration'],
          'recordid' => $ainfo['album']->getID(),
          'number' => $ainfo['position']
      ));
    } else {
      $track = $track[0];
      //If it's set to digitised, throw an error
      if ($track->getDigitised()) {
        return array('status' => 'FAIL', 'error' => 'This track is already in our library.');
      } else {
        //Mark it as digitised
        $track->setDigitised(true);
      }
    }
    
    /**
     * Store three versions of the track:
     * 1- 192kbps MP3 for BAPS and Chrome/IE
     * 2- 192kbps OGG for Safari/Firefox
     * 3- Original file for potential future conversions
     */
    $tmpfile = Config::$audio_upload_tmp_dir.'/'.$tmpid;
    $dbfile = $ainfo['album']->getFolder().'/'.$track->getID();
    
    shell_exec("nice -n 15 ffmpeg -i '$tmpfile' -ab 192k -f mp3 - >'{$dbfile}.mp3'");
    shell_exec("nice -n 15 ffmpeg -i '$tmpfile' -acodec libvorbis -ab 192k '{$dbfile}.ogg'");
    rename($tmpfile, $dbfile.'.mp3.orig');
    
    return array('status' => 'OK');
  }
  
  /**
   * Create a new MyURY_Track with the provided options
   * @param Array $options
   * title (required): Title of the track.
   * artist (required): (string) Artist of the track.
   * recordid (required): (int)Album of track.
   * duration (required): Duration of the track, in seconds
   * number: Position of track on album
   * genre: Character code genre of track
   * intro: Length of track intro, in seconds
   * clean: 'y' yes, 'n' no, 'u' unknown lyric cleanliness status
   * digitised: boolean digitised status
   * @return MyURY_Track a shiny new MyURY_Track with the provided options
   * @throws MyURYException
   */
  public static function create($options) {
    self::__wakeup();
    
    $required = array('title', 'artist', 'recordid', 'duration');
    foreach ($required as $require) {
      if (empty($options[$require])) throw new MyURYException($require.' is required to create a Track.', 400);
    }
    
    //Number 0
    if (empty($options['number'])) $options['number'] = 0;
    //Other Genre
    if (empty($options['genre'])) $options['genre'] = 'o';
    //No intro
    if (empty($options['intro'])) $options['intro'] = 0;
    //Clean unknown
    if (empty($options['clean'])) $options['clean'] = 'u';
    //Not digitised, and formate to t/f
    if (empty($options['digitised'])) $options['digitised'] = 'f';
      else $options['digitised'] = $options['digitised'] ? 't' : 'f';
    
    $result = self::$db->query('INSERT INTO rec_track (number, title, artist, length, genre, intro, clean, recordid,
      digitised, digitisedby, duration) VALUES ($1, $2, $3, $4, $5, $6, $7, $8, $9, $10, $11) RETURNING trackid',
            array(
               $options['number'],
                trim($options['title']),
                trim($options['artist']),
                CoreUtils::intToTime($options['duration']),
                $options['genre'],
                CoreUtils::intToTime($options['intro']),
                $options['clean'],
                $options['recordid'],
                $options['digitised'],
                $_SESSION['memberid'],
                $options['duration']
            ));
    
    $id = self::$db->fetch_all($result);
    
    return self::getInstance($id[0]['trackid']);
  }
  
  public function updateInfoFromLastfm() {
    $details = self::getAlbumDurationAndPositionFromLastfm($this->title, $this->artist);
    
    $this->setAlbum($details['album']);
    $this->setPosition($details['position']);
    $this->setLength($details['duration']);
  }
  
  public function setAlbum(MyURY_Album $album) {
    $this->album = $album;
    self::$db->query('UPDATE rec_track SET recordid=$1 WHERE trackid=$2', array($album->getID(), $this->getID()));
  }
  
  public function setPosition($position) {
    $this->position = (int)$position;
    self::$db->query('UPDATE rec_track SET number=$1 WHERE trackid=$2', array($this->getPosition(), $this->getID()));
  }
  
  public function getPosition() {
    return $this->position;
  }
  
  public function setLength($length) {
    $this->length = (int)$length;
    self::$db->query('UPDATE rec_track SET length=$1, duration=$2 WHERE trackid=$3', array(
       CoreUtils::intToTime($this->getLength()),
        $this->getLength(),
        $this->getID()
    ));
  }
  
  /**
   * Returns all Tracks that are marked as digitsed in the library
   * 
   * @return MyURY_Track[] An array of digitised Tracks
   */
  public static function getAllDigitised() {
    $ids = self::$db->fetch_column('SELECT trackid FROM rec_track WHERE digitised=\'t\'');
    
    $tracks = array();
    foreach ($ids as $id) {
      $tracks[] = self::getInstance($id);
    }
    
    return $tracks;
  }
  
  /**
   * Returns the physical path to the Track
   * @param String $format Optional file extension - at time of writing this could me "mp3", "ogg" or "mp3.orig"
   * @return String path to Track file
   */
  public function getPath($format = 'mp3') {
    return Config::$music_central_db_path . '/records/' . $this->getAlbum()->getID() . '/' . $this->getID().'.'.$format;
  }
  
  /**
   * Returns whether this track's physical file exists
   * @param String $format Optional file extension - at time of writing this could me "mp3", "ogg" or "mp3.orig"
   * @return bool If the file exists
   */
  public function checkForAudioFile($format = 'mp3') {
    return file_exists($this->getPath($format));
  }
  
  /**
   * Queries the last.fm API to find information about a track with the given title/artist combination
   * @param String $title track title
   * @param String $artist track artist
   * @return array album: MyURY_Album object matching the input
   *               position: The track number on the album
   *               duration: The length of the track, in seconds
   */
  private static function getAlbumDurationAndPositionFromLastfm($title, $artist) {
    $details = json_decode(file_get_contents(
            'http://ws.audioscrobbler.com/2.0/?method=track.getInfo&api_key='
            .Config::$lastfm_api_key
            .'&artist='.urlencode($artist)
            .'&track='.urlencode(str_replace(' (Radio Edit)', '', $title))
            .'&format=json'), true);
    
    if (!isset($details['track']['album'])) {
      //Send some defaults for album info
      return array(
          'album' => MyURY_Album::findOrCreate('URY Downloads '.date('Y'), 'URY'),
          'position' => 0,
          'duration' => intval($details['track']['duration']/1000)
      );
    }
    
    return array(
        'album' => MyURY_Album::findOrCreate($details['track']['album']['title'], $details['track']['album']['artist']),
        'position' => (int)$details['track']['album']['@attr']['position'],
        'duration' => intval($details['track']['duration']/1000)
            );
  }
}
