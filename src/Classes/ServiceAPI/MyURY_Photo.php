<?php

/**
 * This file provides the Photo class for MyURY
 * @package MyURY_Core
 */

/**
 * The Photo class stores and manages information about a URY Photo
 * 
 * @version 20130731
 * @author Lloyd Wallis <lpw@ury.org.uk>
 * @package MyURY_Core
 * @uses \Database
 */
class MyURY_Photo extends ServiceAPI {

  /**
   * @var MyURY_Photo[]
   */
  private static $photos = array();

  /**
   * Stores the primary key for the Photo
   * @var int
   */
  private $photoid;

  /**
   * Stores the User that created this Photo
   * @var User
   */
  private $owner;

  /**
   * Stores when the Photo was uploaded
   * @var int
   */
  private $date_added;
  
  /**
   * The file extension of the photo
   * @var String
   */
  private $format;

  /**
   * Initiates the MyURY_Photo object
   * @param int $photo The ID of the Photo to initialise
   */
  private function __construct($photoid) {
    $this->photoid = $photoid;

    $result = self::$db->fetch_one('SELECT * FROM myury.photos WHERE photoid=$1', array($photoid));
    if (empty($result)) {
      throw new MyURYException('Photo ' . $photoid . ' does not exist!');
      return null;
    }

    $this->owner = User::getInstance($result['owner']);
    $this->date_added = strtotime($result['date_added']);
  }

  public static function getInstance($photoid = -1) {
    self::wakeup();
    if (!is_numeric($photoid)) {
      throw new MyURYException('Invalid Photo ID!');
    }

    if (!isset(self::$photos[$photoid])) {
      self::$photos[$photoid] = new self($photoid);
    }

    return self::$photos[$photoid];
  }
  
  /**
   * Get the unique ID of this Photo
   * @return int
   */
  public function getID() {
    return $this->photoid;
  }
  
  /**
   * Get the User that owns this Photo
   * @return User
   */
  public function getOwner() {
    return $this->owner;
  }

  /**
   * Get the web URL for loading this Photo
   * @return String
   */
  public function getURL() {
    return Config::$public_media_uri.'/image_meta/MyURYImageMetadata/'.$this->getID().'.'.$this->format;
  }
  
  /**
   * Get the file system path to the Photo
   * @return String
   */
  public function getURI() {
    return Config::$public_media_path.'/image_meta/MyURYImageMetadata/'.$this->getID().'.'.$this->format;
  }
  
  /**
   * Add a Photo
   * @param String $tmp_file The path to the temporary file that is the image.
   * @return MyURY_Photo
   */
  public static function create($tmp_file) {
    if (!file_exists($tmp_file)) {
      throw new MyURYException('Photo path '.$tmp_file.' does not exist!', 400);
    }
    
    $format = explode('/',finfo_file(finfo_open(FILEINFO_MIME_TYPE), $tmp_file))[1];
    
    $result = self::$db->fetch_column('INSERT INTO myury.photos (owner, format) VALUES ($1, $2) RETURNING photoid',
      [User::getInstance()->getID(), $format]);
    $id = $result[0];
    $photo = self::getInstance($id);
    if (!move_uploaded_file($tmp_file, $photo->getURI())) {
      self::$db->query('DELETE FROM myury.photos WHERE photoid=$1', [$id]);
      throw new MyURYException('Failed to move new Photo from '.$tmp_file.' to '.$photo->getURI().'. Are permissions for the destination right?', 500);
    }
    return $photo;
  }

}
