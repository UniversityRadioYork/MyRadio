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
    }

    $this->alt = $result['alt'];
    $this->target = $result['target'];
    $this->type = (int) $result['banner_type_id'];

    if (is_numeric($result['photoid'])) {
      parent::__construct($result['photoid']);
    } else {
      parent::__construct(Config::$photo_joined);
    }

    $this->campaigns = self::$db->fetch_column('SELECT banner_campaign_id FROM website.banner_campaign
      WHERE banner_id=$1', [$this->banner_id]);
  }

  public function toDataSource() {
    $data = [
        'banner_id' => $this->getBannerID(),
        'alt' => $this->getAlt(),
        'target' => $this->getTarget(),
        'num_campaigns' => sizeof($this->getCampaigns()),
        'is_active' => $this->isActive(),
        'edit_link' => [
            'display' => 'icon',
            'value' => 'pencil',
            'title' => 'Click here to edit this Banner',
            'url' => CoreUtils::makeURL('Website', 'editBanner', array('bannerid' => $this->getBannerID()))
        ],
        'campaigns_link' => [
            'display' => 'icon',
            'value' => 'calendar',
            'title' => 'Click here to view the Campaigns for this Banner',
            'url' => CoreUtils::makeURL('Website', 'viewCampaigns', array('bannerid' => $this->getBannerID()))
        ]
    ];

    return array_merge(parent::toDataSource(), $data);
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
   * Get the type of the Banner
   * @return int
   */
  public function getType() {
    return $this->type;
  }

  /**
   * Get all the campaigns that this Banner has
   * @return MyURY_BannerCampaign[]
   */
  public function getCampaigns() {
    return MyURY_BannerCampaign::resultSetToObjArray($this->campaigns);
  }

  /**
   * Get if any Campaigns linked to this Banner are active
   * @return boolean
   */
  public function isActive() {
    foreach ($this->getCampaigns() as $campaign) {
      if ($campaign->isActive()) {
        return true;
      }
    }

    return false;
  }
  
  public function getEditForm() {
    return self::getBannerForm()
            ->editMode($this->getBannerID(),
                    [
                        'alt' => $this->getAlt(),
                        'target' => $this->getTarget(),
                        'type' => $this->getType()
                    ], 'doEditBanner');
  }
  
  /**
   * Set the Alt text
   * @param String $alt
   * @return \MyURY_Banner
   * @throws MyURYException
   */
  public function setAlt($alt) {
    if (empty($alt)) {
      throw new MyURYException('Banner Alt cannot be empty!', 400);
    }
    $this->alt = $alt;
    self::$db->query('UPDATE website.banner SET alt=$1 WHERE banner_id=$2', [$alt, $this->getBannerID()]);
    
    return $this;
  }
  
  /**
   * Set the Target URL
   * @param String $target
   * @return \MyURY_Banner
   */
  public function setTarget($target) {
    $this->target = $target;
    self::$db->query('UPDATE website.banner SET target=$1 WHERE banner_id=$2', [$target, $this->getBannerID()]);
    
    return $this;
  }
  
  /**
   * Set the Banner Type
   * @param int $type
   * @return \MyURY_Banner
   * @throws MyURYException
   */
  public function setType($type) {
    if (empty($type) or !is_int($type)) {
      throw new MyURYException('Banner Type must be a number!', 400);
    }
    
    $this->type = $type;
    self::$db->query('UPDATE website.banner SET banner_type_id=$1 WHERE banner_id=$2', [$type, $this->getBannerID()]);
    
    return $this;
  }
  
  /**
   * Set the Banner Photo
   * @param MyURY_Photo $photo
   * @return \MyURY_Banner
   */
  public function setPhoto(MyURY_Photo $photo) {
    parent::__construct($photo->getID());
    self::$db->query('UPDATE website.banner SET image=$1, photoid=$2 WHERE banner_id=$2',
            [str_replace(Config::$public_media_uri.'/','',$photo->getURL()), $this->getID(), $this->getBannerID()]);
    
    return $this;
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

  /**
   * Get ALL the Banners
   * @return MyURY_Banner[]
   */
  public static function getAllBanners() {
    return self::resultSetToObjArray(self::$db->fetch_column('SELECT banner_id FROM website.banner'));
  }

  public static function getBannerTypes() {
    return self::$db->fetch_all('SELECT banner_type_id, description FROM website.banner_type');
  }

  /**
   * Generates the form used to Create and Edit Banners
   * @return MyURY_Form
   */
  public static function getBannerForm() {
    return (new MyURYForm('bannerfrm', 'Website', 'doCreateBanner', ['title' => 'Edit Banner']))
            ->addField(new MyURYFormField('alt', MyURYFormField::TYPE_TEXT, [
                'label' => 'Title',
                'explanation' => 'This is used on the backpages to identify the Banner, and also on the main website as mouseover text.'
            ]))
            ->addField(new MyURYFormField('target', MyURYFormField::TYPE_TEXT, [
                'label' => 'Action',
                'explanation' => 'This is the URL that the User will be taken to if they click the Banner. You can leave this blank for there to not be a link.',
                'required' => false
            ]))
            ->addField(new MyURYFormField('type', MyURYFormField::TYPE_SELECT, [
                'label' => 'Type',
                'explanation' => 'TODO: Ask Matt what this is even supposed to do.',
                'options' => array_map(function($x) {
                          return ['value' => $x['banner_type_id'], 'text' => $x['description']];
                        }, self::getBannerTypes())
            ]))
            ->addField(new MyURYFormField('photo', MyURYFormField::TYPE_FILE, [
                'label' => 'Image',
                'explanation' => 'Please upload a 640x212px image file to use as the Banner.'
            ]));
  }

}
