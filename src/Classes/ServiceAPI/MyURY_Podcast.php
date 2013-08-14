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
    
    $result = self::$db->fetch_one('SELECT file, memberid, approvedid,
      (SELECT array(SELECT metadata_key_id FROM uryplayer.podcast_metadata
        WHERE podcast_id=$1 AND effective_from <= NOW()
        ORDER BY effective_from, podcast_metadata_id)) AS metadata_types,
      (SELECT array(SELECT metadata_value FROM uryplayer.podcast_metadata
        WHERE podcast_id=$1 AND effective_from <= NOW()
        ORDER BY effective_from, show_metadata_id)) AS metadata,
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
    
    var_dump($result);
    
  }

}