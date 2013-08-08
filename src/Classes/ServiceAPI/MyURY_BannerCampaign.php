<?php

/**
 * This file provides the BannerCampaign class for MyURY
 * @package MyURY_Website
 */

/**
 * The BannerCampaign class stores and manages information about a Banner Campaign on the front website
 * 
 * @version 20130806
 * @author Lloyd Wallis <lpw@ury.org.uk>
 * @package MyURY_Website
 * @uses \Database
 */
class MyURY_BannerCampaign extends ServiceAPI {

  /**
   * @var MyURY_BannerCampaign[]
   */
  public static $bannerCampaigns = [];

  /**
   * The ID of the BannerCampaign
   * @var int
   */
  private $banner_campaign_id;

  /**
   * The Banner this is a Campaign for
   * @var MyURY_Banner
   */
  private $banner;

  /**
   * The User that created this Banner Campaign
   * @var User
   */
  private $created_by;

  /**
   * The User that approved this Banner Campaign
   * @var User
   */
  private $approved_by;

  /**
   * The time this Banner Campaign is active from
   * @var int
   */
  private $effective_from;

  /**
   * The time this Banner Campaign is active to
   * @var int
   */
  private $effective_to;

  /**
   * The ID of the location of the Banner (e.g. index)
   * @var int
   */
  private $banner_location_id;

  /**
   * A 2D array of timeslots where this Banner Campaign is visible,
   * with day of week and timeslots. This is repeated every week during an active campaign.
   * 
   * Format:<br>
   * [[id: 69, day: 1, starttime: "00:00:00", endtime: "00:00:00", order: 5]]<br>
   * Where day is [Matt doesn't even know.], and order is the order the banner appears in on the scrolling
   * slideshow. A higher number appears first.
   * 
   * @var Array[]
   */
  private $timeslots;

  /**
   * Initiates the MyURY_BannerCampaign object
   * @param int $banner_campaign_id The ID of the Banner Campaign to initialise
   */
  private function __construct($banner_campaign_id) {
    $this->banner_campaign_id = (int) $banner_campaign_id;

    $result = self::$db->fetch_one('SELECT * FROM website.banner_campaign WHERE banner_campaign_id=$1', array($banner_campaign_id));
    if (empty($result)) {
      throw new MyURYException('Banner Campaign ' . $banner_campaign_id . ' does not exist!');
    }

    $this->banner = MyURY_Banner::getInstance($result['banner_id']);
    $this->created_by = User::getInstance($result['memberid']);
    $this->approved_by = empty($result['approvedid']) ? null : User::getInstance($result['approvedid']);
    $this->effective_from = strtotime($result['effective_from']);
    $this->effective_to = empty($result['effective_to']) ? null : strtotime($result['effective_to']);
    $this->banner_location_id = (int) $result['banner_location_id'];

    //Make times be in seconds since midnight
    $this->timeslots = array_map(function($x) {
      return ['id' => $x['id'], 'day' => $x['day'],
          'start_time' => strtotime($x['start_time'], 0),
          'end_time' => strtotime($x['end_time'], 0)];
    }, self::$db->fetch_all('SELECT id, day, start_time, end_time, \'order\' FROM website.banner_timeslot
      WHERE banner_campaign_id=$1', [$this->banner_campaign_id]));
  }

  /**
   * Returns data about the Campaign
   * @param bool $full If true, returns full, detailed data about the timeslots in this campaign
   * @return Array
   */
  public function toDataSource($full = false) {
    $data = [
        'banner_campaign_id' => $this->getID(),
        'created_by' => $this->getCreatedBy()->getID(),
        'approved_by' => ($this->getApprovedBy() == null) ? null : $this->getApprovedBy()->getID(),
        'effective_from' => CoreUtils::happyTime($this->getEffectiveFrom()),
        'effective_to' => ($this->getEffectiveTo() === null) ? 'Never' : CoreUtils::happyTime($this->getEffectiveTo()),
        'banner_location_id' => $this->getLocation(),
        'num_timeslots' => sizeof($this->getTimeslots()),
        'edit_link' => [
            'display' => 'icon',
            'value' => 'pencil',
            'title' => 'Click here to edit this Campaign',
            'url' => CoreUtils::makeURL('Website', 'editCampaign', ['campaignid' => $this->getID()])
        ]
    ];

    if ($full) {
      $data['timeslots'] = $this->getTimeslots();
    }

    return $data;
  }

  /**
   * Get the ID of the BannerCampaign
   * @return int
   */
  public function getID() {
    return $this->banner_campaign_id;
  }

  /**
   * Get the User that created this Campaign
   * @return User
   */
  public function getCreatedBy() {
    return $this->created_by;
  }

  /**
   * Get the User that approved this Campaign
   * @return User
   */
  public function getApprovedBy() {
    return $this->approved_by;
  }

  /**
   * Get the time (as epoch int) that this Campaign starts.
   * @return int
   */
  public function getEffectiveFrom() {
    return $this->effective_from;
  }

  /**
   * Get the time (as epoch int) that this campaign ends.
   * Returns null if the Campaign does not end.
   * @return int
   */
  public function getEffectiveTo() {
    return $this->effective_to;
  }

  /**
   * Get the ID of the Banner Location
   * @return int
   */
  public function getLocation() {
    return $this->banner_location_id;
  }

  /**
   * Get an array of times during the Active period that the Campaign is visible on the Website.
   * @return Array [[day: 1, start_time: 0, end_time: 86399], ...]
   */
  public function getTimeslots() {
    return $this->timeslots;
  }

  /**
   * Get the Banner this is a Campaign for
   * @return MyURY_Banner
   */
  public function getBanner() {
    return $this->banner;
  }

  /**
   * Returns a MyURYForm filled in and ripe for being used to edit this Campaign.
   * @return MyURYForm
   */
  public function getEditForm() {
    return $this->getBannerCampaignForm($this->banner->getID())
                    ->editMode($this->getID(), [
                        timeslots => $this->getTimeslots(),
                        effective_from => CoreUtils::happyTime($this->getEffectiveFrom()),
                        effective_to => $this->getEffectiveTo() === null ? null :
                                CoreUtils::happyTime($this->getEffectiveTo()),
                        location => $this->getLocation()
                            ], 'doEditCampaign');
  }

  /**
   * Return if this Banner Campaign is currently active. That is, it has started and has not expired.
   * It returns true even when there isn't currently a Banner Timeslot for the Campaign running.
   * @return boolean
   */
  public function isActive() {
    return $this->effective_from <= time() && ($this->effective_to == null or $this->effective_to > time());
  }

  /**
   * Get or create the BannerCampaign object
   * @param int $banner_campaign_id
   * @return MyURY_BannerCampaign
   * @throws MyURYException
   */
  public static function getInstance($banner_campaign_id = -1) {
    self::wakeup();
    if (!is_numeric($banner_campaign_id)) {
      throw new MyURYException('Invalid Banner Campaign ID!');
    }

    if (!isset(self::$bannerCampaigns[$banner_campaign_id])) {
      self::$bannerCampaigns[$banner_campaign_id] = new self($banner_campaign_id);
    }

    return self::$bannerCampaigns[$banner_campaign_id];
  }

  /**
   * Creates a new Banner Campaign
   * @param MyURY_Banner $banner The Banner that is being Campaigned
   * @param int $banner_location_id The location of the Banner Campaign. Default 1 (index page)
   * @param int $effective_from Epoch time that the campaign is starts at. Default now.
   * @param int $effective_to Epoch time that the campaign ends at. Default never.
   * @return MyURY_BannerCampaign The new BannerCampaign
   */
  public static function create(MyURY_Banner $banner, $banner_location_id = 1, $effective_from = null, $effective_to = null) {
    if ($effective_from == null) {
      $effective_from = time();
    }

    $result = self::$db->fetch_column('INSERT INTO website.banner_campaign
      (banner_id, banner_location_id, effective_from, effective_to, memberid, approvedid)
      VALUES ($1, $2, $3, $4, $5, $5) RETURNING banner_campaign_id', array($banner->getBannerID(), $banner_location_id,
        CoreUtils::getTimestamp($effective_from),
        CoreUtils::getTimestamp($effective_to), User::getInstance()->getID()));

    return self::getInstance($result[0]);
  }

  /**
   * Get all Banner Campaigns
   * @return MyURY_BannerCampaign[]
   */
  public static function getAllBannerCampaigns() {
    return self::resultSetToObjArray(self::$db->fetch_column('SELECT banner_campaign_id FROM website.banner_campaign'));
  }

  /**
   * Get all the possible Banner Campaign Locations.
   * @return Array
   */
  public static function getCampaignLocations() {
    return self::$db->fetch_all('SELECT banner_location_id AS value, description AS text FROM website.banner_location');
  }

  /**
   * Returns the form needed to create or edit Banner Campaigns.
   * 
   * @param int $bannerid The ID of the Banner that this Campaign will be/is linked to
   * @return MyURYForm
   */
  public static function getBannerCampaignForm($bannerid) {
    return (new MyURYForm('bannercampaignfrm', 'Website', 'doCreateCampaign', [
                template => 'Website/campaignfrm.twig',
                title => 'Edit Banner Campaign'
                    ]))
                    ->addField(new MyURYFormField('effective_from', MyURYFormField::TYPE_DATETIME, [
                        required => true,
                        value => CoreUtils::happyTime(time()),
                        label => 'Start Time',
                        explanation => 'The time from which this Campaign becomes active.'
                    ]))
                    ->addField(new MyURYFormField('effective_to', MyURYFormField::TYPE_DATETIME, [
                        required => false,
                        label => 'End Time',
                        explanation => 'The time at which this Campaign becomes inactive. Leaving this blank means'
                        . ' the Campaign will continue indefinitely.'
                    ]))
                    ->addField(new MyURYFormField('location', MyURYFormField::TYPE_SELECT, [
                        label => 'Location',
                        explanation => 'Choose where on the website this Campaign is run.',
                        options => self::getCampaignLocations()
                    ]))
                    ->addField(new MyURYFormField('timeslots', MyURYFormField::TYPE_WEEKSELECT, [
                        label => 'Timeslots',
                        explanation => 'All times filled in on this schedule (i.e. are purple) are times during the'
                        . ' week that this Campaign is considered active, and therefore appears on the website.'
                        . ' Click a square to toggle it. Click and drag to select lots at once!',
                    ]))
                    ->addField(new MyURYFormField('bannerid', MyURYFormField::TYPE_HIDDEN, [
                        value => $bannerid
    ]));
  }

}
