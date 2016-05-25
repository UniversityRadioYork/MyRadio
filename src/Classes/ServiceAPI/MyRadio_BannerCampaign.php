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
class MyRadio_BannerCampaign extends ServiceAPI
{
    /**
     * The ID of the BannerCampaign.
     *
     * @var int
     */
    private $banner_campaign_id;

    /**
     * The Banner this is a Campaign for.
     *
     * @var MyRadio_Banner
     */
    private $banner;

    /**
     * The User that created this Banner Campaign.
     *
     * @var MyRadio_User
     */
    private $created_by;

    /**
     * The User that approved this Banner Campaign.
     *
     * @var MyRadio_User
     */
    private $approved_by;

    /**
     * The time this Banner Campaign is active from.
     *
     * @var int
     */
    private $effective_from;

    /**
     * The time this Banner Campaign is active to.
     *
     * @var int
     */
    private $effective_to;

    /**
     * The ID of the location of the Banner (e.g. index).
     *
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
     * @var array[]
     */
    private $timeslots;

    /**
     * Initiates the MyRadio_BannerCampaign object.
     *
     * @param int $banner_campaign_id The ID of the Banner Campaign to initialise
     */
    protected function __construct($banner_campaign_id)
    {
        $this->banner_campaign_id = (int) $banner_campaign_id;

        $result = self::$db->fetchOne('SELECT * FROM website.banner_campaign WHERE banner_campaign_id=$1', [$banner_campaign_id]);
        if (empty($result)) {
            throw new MyRadioException('Banner Campaign '.$banner_campaign_id.' does not exist!');
        }

        $this->banner = MyRadio_Banner::getInstance($result['banner_id']);
        $this->created_by = MyRadio_User::getInstance($result['memberid']);
        $this->approved_by = empty($result['approvedid']) ? null : MyRadio_User::getInstance($result['approvedid']);
        $this->effective_from = strtotime($result['effective_from']);
        $this->effective_to = empty($result['effective_to']) ? null : strtotime($result['effective_to']);
        $this->banner_location_id = (int) $result['banner_location_id'];

        //Make times be in seconds since midnight
        $this->timeslots = array_map(
            function ($x) {
                return [
                    'id' => $x['id'], 'day' => $x['day'],
                    'start_time' => strtotime($x['start_time'], 0),
                    'end_time' => strtotime($x['end_time'], 0),
                ];
            },
            self::$db->fetchAll(
                'SELECT id, day, start_time, end_time, \'order\' FROM website.banner_timeslot
                WHERE banner_campaign_id=$1',
                [$this->banner_campaign_id]
            )
        );
    }

    /**
     * Returns data about the Campaign.
     *
     * @param bool $full If true, returns full, detailed data about the timeslots in this campaign
     *
     * @return array
     */
    public function toDataSource($full = false)
    {
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
                'url' => URLUtils::makeURL('Website', 'editCampaign', ['campaignid' => $this->getID()]),
            ],
        ];

        if ($full) {
            $data['timeslots'] = $this->getTimeslots();
        }

        return $data;
    }

    /**
     * Get the ID of the BannerCampaign.
     *
     * @return int
     */
    public function getID()
    {
        return $this->banner_campaign_id;
    }

    /**
     * Get the User that created this Campaign.
     *
     * @return MyRadio_User
     */
    public function getCreatedBy()
    {
        return $this->created_by;
    }

    /**
     * Get the User that approved this Campaign.
     *
     * @return MyRadio_User
     */
    public function getApprovedBy()
    {
        return $this->approved_by;
    }

    /**
     * Get the time (as epoch int) that this Campaign starts.
     *
     * @return int
     */
    public function getEffectiveFrom()
    {
        return $this->effective_from;
    }

    /**
     * Get the time (as epoch int) that this campaign ends.
     * Returns null if the Campaign does not end.
     *
     * @return int
     */
    public function getEffectiveTo()
    {
        return $this->effective_to;
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
     * Get an array of times during the Active period that the Campaign is visible on the Website.
     *
     * @return array [[day: 1, start_time: 0, end_time: 86399], ...]
     */
    public function getTimeslots()
    {
        return $this->timeslots;
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
                    'timeslots' => $this->getTimeslots(),
                    'effective_from' => CoreUtils::happyTime($this->getEffectiveFrom()),
                    'effective_to' => $this->getEffectiveTo() === null ? null :
                        CoreUtils::happyTime($this->getEffectiveTo()),
                    'location' => $this->getLocation(),
                ]
            );
    }

    /**
     * Return if this Banner Campaign is currently active. That is, it has started and has not expired.
     * It returns true even when there isn't currently a Banner Timeslot for the Campaign running.
     *
     * @return bool
     */
    public function isActive()
    {
        return $this->effective_from <= time() && ($this->effective_to == null or $this->effective_to > time());
    }

    /**
     * Removes all timeslots associated with a Banner Campaign.
     *
     * Used when editing, as they are then immediately added again.
     */
    public function clearTimeslots()
    {
        $this->timeslots = [];
        self::$db->query('DELETE FROM website.banner_timeslot WHERE banner_campaign_id=$1', [$this->getID()]);
        $this->updateCacheObject();
    }

    /**
     * Sets the start time of the Campaign.
     *
     * @param int $time
     *
     * @return MyRadio_BannerCampaign
     */
    public function setEffectiveFrom($time)
    {
        $this->effective_from = $time;
        self::$db->query(
            'UPDATE website.banner_campaign SET effective_from=$1 WHERE banner_campaign_id=$2',
            [CoreUtils::getTimestamp($time), $this->getID()]
        );
        $this->updateCacheObject();

        return $this;
    }

    /**
     * Sets the end time of the Campaign.
     *
     * @param int $time
     *
     * @return MyRadio_BannerCampaign
     */
    public function setEffectiveTo($time)
    {
        if ($time === null) {
            $this->effective_to = $time;
            self::$db->query(
                'UPDATE website.banner_campaign SET effective_to=NULL WHERE banner_campaign_id=$1',
                [$this->getID()]
            );
        } else {
            self::$db->query(
                'UPDATE website.banner_campaign SET effective_to=$1 WHERE banner_campaign_id=$2',
                [CoreUtils::getTimestamp($time), $this->getID()]
            );
        }

        $this->updateCacheObject();

        return $this;
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
            'UPDATE website.banner_campaign SET banner_location_id=$1 WHERE banner_campaign_id=$2',
            [$location, $this->getID()]
        );
        $this->updateCacheObject();

        return $this;
    }

    /**
     * Adds an Active Timeslot to the Campaign.
     *
     * @param int $day   Day the timeslot is on. 1 = Monday, 7 = Sunday. Timeslots cannot span days.
     * @param int $start Seconds since midnight that the Timeslot starts.
     * @param int $end   Seconds since midnight that the Timeslot ends.
     *
     * @todo  Input validation.
     */
    public function addTimeslot($day, $start, $end)
    {
        $start = gmdate('H:i:s', $start).'+00';
        $end = gmdate('H:i:s', $end).'+00';

        $id = self::$db->fetchColumn(
            'INSERT INTO website.banner_timeslot
            (banner_campaign_id, memberid, approvedid, "order", day, start_time, end_time)
            VALUES ($1, $2, $2, $1, $3, $4, $5) RETURNING id',
            [
                $this->getID(),
                MyRadio_User::getInstance()->getID(),
                $day,
                $start,
                $end,
            ]
        )[0];

        $this->timeslots[] = [
            'id' => $id,
            'day' => $day,
            'start_time' => strtotime($start, 0),
            'end_time' => strtotime($end, 0),
        ];

        $this->updateCacheObject();
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
            $campaign->addTimeslot($timeslot['day'], $timeslot['start_time'], $timeslot['end_time']);
        }

        return $campaign;
    }

    /**
     * Get all Banner Campaigns.
     *
     * @return MyRadio_BannerCampaign[]
     */
    public static function getAllBannerCampaigns()
    {
        return self::resultSetToObjArray(self::$db->fetchColumn('SELECT banner_campaign_id FROM website.banner_campaign'));
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
     * Gets all Banner Campaigns that are currently live. That is they are active and have timeslots at the current time.
     *
     * @return MyRadio_BannerCampaign[]
     */
    public static function getLiveBannerCampaigns()
    {
        return self::resultSetToObjArray(
            self::$db->fetchColumn(
                'SELECT banner_campaign_id FROM website.banner_campaign
                 LEFT JOIN website.banner_timeslot USING(banner_campaign_id)
                 WHERE effective_from < now()
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
        return self::$db->fetchAll('SELECT banner_location_id AS value, description AS text FROM website.banner_location');
    }

    /**
     * Returns the form needed to create or edit Banner Campaigns.
     *
     * @param int $bannerid The ID of the Banner that this Campaign will be/is linked to
     *
     * @return MyRadioForm
     */
    public static function getForm($bannerid = null)
    {
        return (
            new MyRadioForm(
                'bannercampaignfrm',
                'Website',
                'editCampaign',
                [
                    'template' => 'Website/campaignfrm.twig',
                    'title' => 'Edit Banner Campaign',
                ]
            )
        )
        ->addField(
            new MyRadioFormField(
                'effective_from',
                MyRadioFormField::TYPE_DATETIME,
                [
                    'required' => true,
                    'value' => CoreUtils::happyTime(time()),
                    'label' => 'Start Time',
                    'explanation' => 'The time from which this Campaign becomes active.',
                ]
            )
        )
        ->addField(
            new MyRadioFormField(
                'effective_to',
                MyRadioFormField::TYPE_DATETIME,
                [
                    'required' => false,
                    'label' => 'End Time',
                    'explanation' => 'The time at which this Campaign becomes inactive. Leaving this blank means'
                    .' the Campaign will continue indefinitely.',
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
        )
        ->addField(
            new MyRadioFormField(
                'timeslots',
                MyRadioFormField::TYPE_WEEKSELECT,
                [
                    'label' => 'Timeslots',
                    'explanation' => 'All times filled in on this schedule (i.e. are purple) are times during the'
                    .' week that this Campaign is considered active, and therefore appears on the website.'
                    .' Click a square to toggle it. Click and drag to select lots at once!',
                ]
            )
        )
        ->addField(
            new MyRadioFormField(
                'bannerid',
                MyRadioFormField::TYPE_HIDDEN,
                [
                    'value' => $bannerid,
                ]
            )
        );
    }
}
