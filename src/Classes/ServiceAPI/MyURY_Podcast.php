<?php

/**
 * Provides the MyURY_APIKey class for MyURY
 * @package MyURY_Podcast
 */

/**
 * Podcasts. For the website.
 * 
 * Reminder: Podcasts may not include any copyrighted content. This includes
 * all songs and *beds*.
 * 
 * @version 20130814
 * @author Lloyd Wallis <lpw@ury.org.uk>
 * @package MyURY_Podcast
 * @uses \Database
 */
class MyURY_Podcast extends MyURY_Metadata_Common {

  /**
   * Singleton store.
   * @var MyURY_Podcast[]
   */
  private static $podcasts = [];

  /**
   * The Podcast's ID
   * @var int
   */
  private $podcast_id;

  /**
   * The path to the file, relative to Config::$public_media_uri
   * @var String
   */
  private $file;

  /**
   * The Time the Podcast was uploaded
   * @var int
   */
  private $submitted;

  /**
   * The ID of the User that uploaded the Podcast
   * @var int
   */
  private $memberid;

  /**
   * The ID of the User that approved the Podcast
   * @var int
   */
  private $approvedid;
  
  /**
   * Array of Users and their relation to the Podcast.
   * @var Array
   */
  protected $credits = array();

  /**
   * Get the object for the given Podcast
   * @param int $podcast_id
   * @return MyURY_Podcast
   * @throws MyURYException
   */
  public static function getInstance($podcast_id = null) {
    self::wakeup();
    if ($podcast_id === null) {
      throw new MyURYException('Invalid Podcast ID', 400);
    }

    if (!isset(self::$podcasts[$podcast_id])) {
      self::$podcasts[$podcast_id] = new self($podcast_id);
    }

    return self::$podcasts[$podcast_id];
  }

  /**
   * Construct the API Key Object
   * @param String $key
   */
  private function __construct($podcast_id) {
    $this->podcast_id = $podcast_id;

    $result = self::$db->fetch_one('SELECT file, memberid, approvedid, submitted,
      (SELECT array(SELECT metadata_key_id FROM uryplayer.podcast_metadata
        WHERE podcast_id=$1 AND effective_from <= NOW()
        ORDER BY effective_from, podcast_metadata_id)) AS metadata_types,
      (SELECT array(SELECT metadata_value FROM uryplayer.podcast_metadata
        WHERE podcast_id=$1 AND effective_from <= NOW()
        ORDER BY effective_from, podcast_metadata_id)) AS metadata,
      (SELECT array(SELECT metadata_value FROM uryplayer.podcast_image_metadata
        WHERE podcast_id=$1 AND effective_from <= NOW()
        ORDER BY effective_from, podcast_image_metadata_id)) AS image_metadata,
      (SELECT array(SELECT credit_type_id FROM uryplayer.podcast_credit
         WHERE podcast_id=$1 AND effective_from <= NOW()
           AND (effective_to IS NULL OR effective_to >= NOW())
           AND approvedid IS NOT NULL
         ORDER BY podcast_credit_id)) AS credit_types,
      (SELECT array(SELECT creditid FROM uryplayer.podcast_credit
         WHERE podcast_id=$1 AND effective_from <= NOW()
           AND (effective_to IS NULL OR effective_to >= NOW())
           AND approvedid IS NOT NULL
         ORDER BY podcast_credit_id)) AS credits
      FROM uryplayer.podcast WHERE podcast_id=$1', array($podcast_id));

    if (empty($result)) {
      throw new MyURYException('Podcast ' . $podcast_id, ' does not exist.', 404);
    }

    $this->file = $result['file'];
    $this->memberid = (int) $result['memberid'];
    $this->approvedid = (int) $result['approvedid'];
    $this->submitted = strtotime($result['submitted']);

    //Deal with the Credits arrays
    $credit_types = self::$db->decodeArray($result['credit_types']);
    $credits = self::$db->decodeArray($result['credits']);

    for ($i = 0; $i < sizeof($credits); $i++) {
      if (empty($credits[$i])) {
        continue;
      }
      $this->credits[] = array('type' => (int) $credit_types[$i],
          'memberid' => $credits[$i],
          'User' => User::getInstance($credits[$i]));
    }


    //Deal with the Metadata arrays
    $metadata_types = self::$db->decodeArray($result['metadata_types']);
    $metadata = self::$db->decodeArray($result['metadata']);

    for ($i = 0; $i < sizeof($metadata); $i++) {
      if (self::isMetadataMultiple($metadata_types[$i])) {
        //Multiples should be an array
        $this->metadata[$metadata_types[$i]][] = $metadata[$i];
      } else {
        $this->metadata[$metadata_types[$i]] = $metadata[$i];
      }
    }
  }

  /**
   * Get all the Podcasts that the User is Owner of Creditor of.
   * @param User $user Default current user.
   * @return MyURY_Podcast[]
   */
  public static function getPodcastsAttachedToUser(User $user = null) {
    if ($user === null) {
      $user = User::getInstance();
    }

    $r = self::$db->fetch_column('SELECT podcast_id FROM uryplayer.podcast
      WHERE memberid=$1 OR podcast_id IN
        (SELECT podcast_id FROM uryplayer.podcast_credit
          WHERE creditid=$1 AND effective_from <= NOW() AND
          (effective_to >= NOW() OR effective_to IS NULL))',
          [$user->getID()]);

    return self::resultSetToObjArray($r);
  }
  
  /**
   * Get the Podcast ID
   * @return int
   */
  public function getID() {
    return $this->podcast_id;
  }
  
  /**
   * Get data in array format
   * @param boolean $full If true, returns more data.
   * @return Array
   */
  public function toDataSource($full = true) {
    $data = array(
        'podcast' => $this->getID(),
        'title' => $this->getMeta('title'),
        'description' => $this->getMeta('description'),
        'editlink' => array(
            'display' => 'icon',
            'value' => 'script',
            'title' => 'Edit Podcast',
            'url' => CoreUtils::makeURL('Podcast', 'editPodcast', 
                    array('podcastid' => $this->getID())))
    );
    
    if ($full) {
      $data['credits'] = implode(', ', $this->getCreditsNames(false));
    }
    
    return $data;
  }

}