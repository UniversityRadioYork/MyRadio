<?php
/**
 * This file provides the Banner class for MyURY
 * @package MyURY_Website
 */

/**
 * The Banner class stores and manages information about a Banner on the front website
 * 
 * @version 20130806
 * @author Lloyd Wallis <lpw@ury.org.uk>
 * @package MyURY_Website
 * @uses \Database
 */
class MyURY_Banner extends MyURY_Photo {

  /**
   * @var MyURY_Banner[]
   */
  public static $banners = [];

  /**
   * The ID of the banner
   * @var int
   */
  private $banner_id;

  /**
   * A short text description of the banner
   * @var String
   */
  private $alt;

  /**
   * URL target of the banner. Activated when banner is clicked.
   * @var String
   */
  private $target;

  /**
   * Banner type. No idea what this is, and there's only one type.
   * @var int
   */
  private $type = 2;

  /**
   * IDs of Campaigns that use this Banner
   * @var int[]
   */
  private $campaigns = array();

  /**
   * Initiates the MyURY_Banner object
   * @param int $banner_id The ID of the Banner to initialise
   */
  protected function __construct($banner_id) {
    $this->banner_id = (int) $banner_id;

    $result = self::$db->fetch_one('SELECT * FROM website.banner WHERE banner_id=$1', array($banner_id));
    if (empty($result)) {
      throw new MyURYException('Banner ' . $banner_id . ' does not exist!');
      return null;
    }

    $this->alt = $result['alt'];
    $this->target = $result['target'];
    $this->type = (int) $result['banner_type_id'];

    if (!empty($result['photoid'])) {
      parent::__construct($result['photoid']);
    }

    $this->campaigns = self::$db->fetch_column('SELECT banner_campaign_id FROM website.banner_campaign
      WHERE banner_id=$1', [$this->banner_id]);
  }

  public function toDataSource() {
    $data = [
        'banner_id' => $this->getBannerID(),
        'alt' => $this->getAlt(),
        'target' => $this->getTarget(),
        'num_campaigns' => sizeof($this->getCampaigns())
    ];

    return array_merge($data, parent::toDataSource());
  }
  
  /**
   * Get the ID of the Banner
   * @return int
   */
  public function getBannerID() {
    return $this->banner_id;
  }
  
  /**
   * Get a description of the Banner
   * @return String
   */
  public function getAlt() {
    return $this->alt;
  }
  
  /**
   * Get the link action of the Banner
   * @return String
   */
  public function getTarget() {
    return $this->target;
  }
  
  /**
   * Get all the campaigns that this Banner has
   * @return MyURY_BannerCampaign[]
   */
  public function getCampaigns() {
    return MyURY_BannerCampaign::resultSetToObjArray($this->campaigns);
  }

  /**
   * Get or create the Banner object
   * @param int $banner_id
   * @return MyURY_Banner
   * @throws MyURYException
   */
  public static function getInstance($banner_id = -1) {
    self::wakeup();
    if (!is_numeric($banner_id)) {
      throw new MyURYException('Invalid Banner ID!');
    }

    if (!isset(self::$banners[$banner_id])) {
      self::$banners[$banner_id] = new self($banner_id);
    }

    return self::$banners[$banner_id];
  }

  /**
   * Creates a banner
   * @param MyURY_Photo $photo The Photo this banner will use. Must be 640x212px.
   * @param String $alt Friendly name. Used on backend and as 'alt' text.
   * @param String $target URL clicking the banner takes you to. Should be absolute.
   * @param int $type The type of banner. Currently, there's only one type, intuitively called 2.
   * @return MyURY_Banner The new Banner, of course!
   * @throws MyURYException
   */
  public static function create(MyURY_Photo $photo, $alt = 'Unnamed Banner', $target = null, $type = 2) {
    $result = self::$db->fetch_column('INSERT INTO website.banner (alt, image, target, banner_type_id, photoid)
      VALUES ($1, $2, $3, $4, $5) RETURNING banner_id', array($alt, $photo->getURL(), $target, $type, $photo->getID()));

    return self::getInstance($result[0]);
  }

  public static function getAllBanners() {
    return self::resultSetToObjArray(self::$db->fetch_column('SELECT banner_id FROM website.banner'));
  }

}
