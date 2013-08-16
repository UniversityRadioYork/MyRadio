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
 * @version 20130815
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
   * The ID of the show this is linked to, if any.
   * @var int
   */
  private $show_id;

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
    $this->podcast_id = (int) $podcast_id;

    $result = self::$db->fetch_one('SELECT file, memberid, approvedid, submitted,
      show_id,
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
      FROM uryplayer.podcast
      LEFT JOIN schedule.show_podcast_link USING (podcast_id)
      WHERE podcast_id=$1', array($podcast_id));

    if (empty($result)) {
      throw new MyURYException('Podcast ' . $podcast_id, ' does not exist.', 404);
    }

    $this->file = $result['file'];
    $this->memberid = (int) $result['memberid'];
    $this->approvedid = (int) $result['approvedid'];
    $this->submitted = strtotime($result['submitted']);
    $this->show_id = (int) $result['show_id'];

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
          (effective_to >= NOW() OR effective_to IS NULL))', [$user->getID()]);

    return self::resultSetToObjArray($r);
  }

  public static function getCreateForm() {
    return (new MyURYForm('Create Podcast', 'Podcast', 'doCreatePodcast'))
            ->addField(new MyURYFormField('title', MyURYFormField::TYPE_TEXT, [
                'label' => 'Title'
            ]))->addField(new MyURYFormField('description', MyURYFormField::TYPE_BLOCKTEXT, [
                'label' => 'Description'
            ]))->addField(new MyURYFormField('tags', MyURYFormField::TYPE_TEXT, [
                'label' => 'Tags',
                'explanation' => 'A set of keywords to describe your show '
                . 'generally, seperated with spaces.'
            ]))->addField(new MyURYFormField('credits', MyURYFormField::TYPE_TABULARSET, [
                'label' => 'Credits', 'options' => [
                    new MyURYFormField('member', MyURYFormField::TYPE_MEMBER, [
                        'explanation' => '',
                        'label' => 'Person'
                            ]),
                    new MyURYFormField('credittype', MyURYFormField::TYPE_SELECT, [
                        'options' => array_merge([['text' => 'Please select...',
                        'disabled' => true]], MyURY_Scheduler::getCreditTypes()),
                        'explanation' => '',
                        'label' => 'Role'
              ])]]))->addField(new MyURYFormField('file', MyURYFormField::TYPE_FILE, [
                'label' => 'Audio',
                'explanation' => 'Upload the original, high-quality audio for'
                  . ' this podcast. We\'ll publish a version optimised for the web'
                  . ' and archive the original. Max size 500MB.',
                'options' => ['progress' => true]
            ]))->addField(new MyURYFormField('terms', MyURYFormField::TYPE_CHECK, [
                'label' => 'I have read and confirm that this audio file complies'
                . ' with URY\'s Podcasting Policy.'
            ]));
  }

  /**
   * Get the Podcast ID
   * @return int
   */
  public function getID() {
    return $this->podcast_id;
  }

  public function getShow() {
    if (!empty($this->show_id)) {
      return MyURY_Show::getInstance($this->show_id);
    } else {
      return null;
    }
  }

  /**
   * Get data in array format
   * @param boolean $full If true, returns more data.
   * @return Array
   */
  public function toDataSource($full = true) {
    $data = array(
        'podcast_id' => $this->getID(),
        'title' => $this->getMeta('title'),
        'description' => $this->getMeta('description'),
        'editlink' => array(
            'display' => 'icon',
            'value' => 'script',
            'title' => 'Edit Podcast',
            'url' => CoreUtils::makeURL('Podcast', 'editPodcast', array('podcastid' => $this->getID())))
    );

    if ($full) {
      $data['credits'] = implode(', ', $this->getCreditsNames(false));
      $data['show'] = $this->getShow() ?
              $this->getShow()->toDataSource(false) : null;
    }

    return $data;
  }

  /**
   * Sets a metadata key to the specified value.
   * 
   * If any value is the same as an existing one, no action will be taken.
   * If the given key has is_multiple, then the value will be added as a new, additional key.
   * If the key does not have is_multiple, then any existing values will have effective_to
   * set to the effective_from of this value, effectively replacing the existing value.
   * This will *not* unset is_multiple values that are not in the new set.
   * 
   * @param String $string_key The metadata key
   * @param mixed $value The metadata value. If key is_multiple and value is an array, will create instance
   * for value in the array.
   * @param int $effective_from UTC Time the metavalue is effective from. Default now.
   * @param int $effective_to UTC Time the metadata value is effective to. Default NULL (does not expire).
   * @param null $table Used for compatibility with parent.
   * @param null $pkey Used for compatibility with parent.
   */
  public function setMeta($string_key, $value, $effective_from = null, $effective_to = null, $table = null, $pkey = null) {
    parent::setMeta($string_key, $value, $effective_from, $effective_to, 'uryplayer.podcast_metadata', 'podcast_id');
  }

}