<?php

/**
 * This file provides the BannerCampaign class for MyRadio.
 */
namespace MyRadio\ServiceAPI;

use MyRadio\MyRadioException;
use MyRadio\MyRadio\CoreUtils;
use MyRadio\MyRadio\URLUtils;
use MyRadio\MyRadio\MyRadioForm;
use MyRadio\MyRadio\MyRadioFormField;

/**
 * The BannerCampaign class stores and manages information about a Banner Campaign on the front website.
 *
 * @uses    \Database
 */
class MyRadio_BannerCampaign extends \MyRadio\MyRadio\MyRadio_Availability
{
    /**
     * The Banner this is a Campaign for.
     *
     * @var MyRadio_Banner
     */
    private $banner;

    /**
     * The ID of the location of the Banner (e.g. index).
     *
     * @var int
     */
    private $banner_location_id;

    /**
     * Initiates the MyRadio_BannerCampaign object.
     *
     * @param int $banner_campaign_id The ID of the Banner Campaign to initialise
     */
    protected function __construct($banner_campaign_id)
    {
        $result = $this->setAvailability(
            $banner_campaign_id,
            'website.banner_campaign',
            'website.banner_timeslot',
            'banner_campaign_id'
        );

        $this->banner = MyRadio_Banner::getInstance($result['banner_id']);
        $this->banner_location_id = (int) $result['banner_location_id'];
    }

    /**
     * Returns data about the Campaign.
     * @param array $mixins Mixins.
     * @mixin timeslots Provides data about the timeslots in this campaign
     * @return array
     */
    public function toDataSource($mixins = [])
    {
        $data = parent::toDataSource($mixins);
        $data['banner_location_id'] = $this->getLocation();
        $data['edit_link'] = [
            'display' => 'icon',
            'value' => 'pencil',
            'title' => 'Click here to edit this Campaign',
            'url' => URLUtils::makeURL('Website', 'editCampaign', ['campaignid' => $this->getID()]),
        ];

        return $data;
    }

    /**
     * Get the ID of the Banner Location.
     *
     * @return int
     */
    public function getLocation()
    {
        return $this->banner_location_id;
    }

    /**
     * Get the Banner this is a Campaign for.
     *
     * @return MyRadio_Banner
     */
    public function getBanner()
    {
        return $this->banner;
    }

    /**
     * Returns a MyRadioForm filled in and ripe for being used to edit this Campaign.
     *
     * @return MyRadioForm
     */
    public function getEditForm()
    {
        return $this->getForm($this->banner->getID())
            ->editMode(
                $this->getID(),
                [
                    'availabilityid' => $this->getID(),
                    'timeslots' => $this->getTimeslots(),
                    'effective_from' => CoreUtils::happyTime($this->getEffectiveFrom()),
                    'effective_to' => $this->getEffectiveTo() === null ? null :
                        CoreUtils::happyTime($this->getEffectiveTo()),
                    'location' => $this->getLocation(),
                ]
            );
    }

    /**
     * Sets the location of the Campaign.
     *
     * @param int $location
     *
     * @return MyRadio_BannerCampaign
     */
    public function setLocation($location)
    {
        $this->banner_location_id = $location;
        self::$db->query(
            "UPDATE $this->availability_table SET banner_location_id=$1 WHERE $this->id_field=$2",
            [$location, $this->getID()]
        );
        $this->updateCacheObject();

        return $this;
    }

    /**
     * Creates a new Banner Campaign.
     *
     * @param MyRadio_Banner $banner             The Banner that is being Campaigned
     * @param int            $banner_location_id The location of the Banner Campaign. Default 1 (index page)
     * @param int            $effective_from     Epoch time that the campaign is starts at. Default now.
     * @param int            $effective_to       Epoch time that the campaign ends at. Default never.
     * @param array          $timeslots          An array of Timeslots the Campaign is active during.
     *
     * @return MyRadio_BannerCampaign The new BannerCampaign
     */
    public static function create(
        MyRadio_Banner $banner,
        $banner_location_id = 1,
        $effective_from = null,
        $effective_to = null,
        $timeslots = []
    ) {
        if ($effective_from == null) {
            $effective_from = time();
        }

        $result = self::$db->fetchColumn(
            'INSERT INTO website.banner_campaign
            (banner_id, banner_location_id, effective_from, effective_to, memberid, approvedid)
            VALUES ($1, $2, $3, $4, $5, $5) RETURNING banner_campaign_id',
            [
                $banner->getBannerID(),
                $banner_location_id,
                CoreUtils::getTimestamp($effective_from),
                CoreUtils::getTimestamp($effective_to),
                MyRadio_User::getInstance()->getID(),
            ]
        );

        $campaign = self::getInstance($result[0]);

        foreach ($timeslots as $timeslot) {
            $campaign->addTimeslot($timeslot['day'], $timeslot['start_time'], $timeslot['end_time'], 'order',$result[0]);
        }
        $campaign->updateCacheObject();
        return $campaign;
    }

    /**
     * Get all Banner Campaigns.
     *
     * @return MyRadio_BannerCampaign[]
     */
    public static function getAllBannerCampaigns()
    {
        return parent::getAllAvailabilities();
    }

    /**
     * Gets all Banner Campaigns that are currently active. That is, they have started and have not expired.
     * It returns them even when there isn't currently a Banner Timeslot for the Campaign running.
     *
     * @return MyRadio_BannerCampaign[]
     */
    public static function getActiveBannerCampaigns()
    {
        return self::resultSetToObjArray(
            self::$db->fetchColumn(
                'SELECT banner_campaign_id FROM website.banner_campaign
                WHERE effective_from < now()
                AND (effective_to IS NULL
                    OR effective_to > now())'
            )
        );
    }

    /**
     * Gets all currently live Banner Campaigns. That is they are active and have timeslots at the current time.
     *
     * @return MyRadio_BannerCampaign[]
     */
    public static function getLiveBannerCampaigns()
    {
        return self::resultSetToObjArray(
            self::$db->fetchColumn(
                'SELECT website.banner_campaign.banner_campaign_id FROM website.banner_campaign, website.banner_timeslot
                WHERE website.banner_campaign.banner_campaign_id = website.banner_timeslot.banner_campaign_id
                AND effective_from < now()
                AND (effective_to IS NULL
                    OR effective_to > now())
                AND day = EXTRACT(ISODOW FROM now())
                AND start_time < localtime
                AND end_time > localtime
                ORDER BY "order" ASC'
            )
        );
    }

    /**
     * Get all the possible Banner Campaign Locations.
     *
     * @return array
     */
    public static function getCampaignLocations()
    {
        return self::$db->fetchAll(
            'SELECT banner_location_id AS value, description AS text FROM website.banner_location'
        );
    }

    /**
     * Returns the form needed to create or edit Banner Campaigns.
     *
     * @param int $newBannerID The ID of the Banner that this Campaign will be created for
     * @param int $availabilityid The ID of the campaign that will be edited
     *
     * @return MyRadioForm
     */
    public static function getForm($availabilityid = null, $newBannerID = null)
    {
        return parent::getFormBase(
            $availabilityid,
            'Website',
            'editCampaign',
            ['template' => 'Website/campaignfrm.twig', 'title' => 'Edit Banner Campaign']
        )
        ->addField(
            new MyRadioFormField(
                'newbannerid',
                MyRadioFormField::TYPE_HIDDEN,
                [
                    'required' => false,
                    'value' => $newBannerID
                ]
            )
        )
        ->addField(
            new MyRadioFormField(
                'location',
                MyRadioFormField::TYPE_SELECT,
                [
                    'label' => 'Location',
                    'explanation' => 'Choose where on the website this Campaign is run.',
                    'options' => self::getCampaignLocations(),
                ]
            )
        );
    }
}
