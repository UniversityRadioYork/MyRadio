<?php

/**
 * This file provides the Availability class for MyRadio.
 *
 * Availabilities are generalised ways of providing time ranges where something
 * is active based on the day of week/time with start and end times (See the
 * WEEKSELECT FormField). This kind of functionality is used by components such
 * as Playlists and Banners.
 */
namespace MyRadio\MyRadio;

use MyRadio\MyRadioException;
use MyRadio\MyRadio\CoreUtils;
use MyRadio\MyRadio\MyRadioForm;
use MyRadio\MyRadio\MyRadioFormField;
use MyRadio\ServiceAPI\MyRadio_User;

class MyRadio_Availability extends \MyRadio\ServiceAPI\ServiceAPI
{
    /**
     * The table the Availability is stored in.
     * Overriden by implementations.
     *
     * @var string
     */
    protected $availability_table;

    /**
     * The table the Availability timeslots is stored in.
     * Overriden by implementations.
     *
     * @var string
     */
    protected $timeslot_table;

    /**
     * The id field the Availability is stored in.
     * Overriden by implementations.
     *
     * @var string
     */
    protected $id_field = 'id';

    /**
     * The ID of the Availability.
     *
     * @var int
     */
    private $id;

    /**
     * The User that created this Availability.
     *
     * @var MyRadio_User
     */
    private $created_by;

    /**
     * The User that approved this Availability.
     *
     * @var MyRadio_User
     */
    private $approved_by;

    /**
     * The time this Availability is active from.
     *
     * @var int
     */
    private $effective_from;

    /**
     * The time this Availability is active to.
     *
     * @var int
     */
    private $effective_to;

    /**
     * A 2D array of timeslots where this Availability is visible,
     * with day of week and timeslots. This is repeated every week during an active campaign.
     *
     * Format:
     * [[id: 69, day: 1, starttime: "00:00:00", endtime: "00:00:00", order: 5]]
     * Where Monday is 1, and order is the order the banner appears in on the scrolling
     * slideshow. A higher number appears first.
     *
     * @var array[]
     */
    private $timeslots;

    /**
     * Initiates the MyRadio_Availability object.
     *
     * @param int    $id       The ID of the Availability to initialise
     * @param string $table
     * @param string $id_field
     */
    protected function __construct($id, $result = null)
    {
        $this->id = (int) $id;

        if ($result === null) {
            $result = self::$db->fetchOne(
                'SELECT * FROM '.$this->availability_table.' WHERE '.$this->id_field.'=$1',
                [$id]
            );
        }
        if (empty($result)) {
            throw new MyRadioException('Availability '.$id.' does not exist!');
        }

        $this->created_by = MyRadio_User::getInstance($result['memberid']);
        $this->approved_by = empty($result['approvedid']) ? null : MyRadio_User::getInstance($result['approvedid']);
        $this->effective_from = strtotime($result['effective_from']);
        $this->effective_to = empty($result['effective_to']) ? null : strtotime($result['effective_to']);

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
                'SELECT id, day, start_time, end_time, \'order\' FROM '.$this->timeslot_table.'
                WHERE '.$this->id_field.'=$1',
                [$this->id]
            )
        );
    }

    /**
     * Returns data about the Availability.
     *
     * @param bool $full If true, returns full, detailed data about the timeslots in this campaign
     *
     * @return array
     */
    public function toDataSource($full = false)
    {
        $data = [
            'id' => $this->getID(),
            'created_by' => $this->getCreatedBy()->getID(),
            'approved_by' => ($this->getApprovedBy() == null) ? null : $this->getApprovedBy()->getID(),
            'effective_from' => CoreUtils::happyTime($this->getEffectiveFrom()),
            'effective_to' => ($this->getEffectiveTo() === null) ?
                                   'Never' : CoreUtils::happyTime($this->getEffectiveTo()),
            'num_timeslots' => sizeof($this->getTimeslots()),
        ];

        if ($full) {
            $data['timeslots'] = $this->getTimeslots();
        }

        return $data;
    }

    /**
     * Get the ID of the Availability.
     *
     * @return int
     */
    public function getID()
    {
        return $this->id;
    }

    /**
     * Get the User that created this Availability.
     *
     * @return MyRadio_User
     */
    public function getCreatedBy()
    {
        return $this->created_by;
    }

    /**
     * Get the User that approved this Availability.
     *
     * @return MyRadio_User
     */
    public function getApprovedBy()
    {
        return $this->approved_by;
    }

    /**
     * Get the time (as epoch int) that this Availability starts.
     *
     * @return int
     */
    public function getEffectiveFrom()
    {
        return $this->effective_from;
    }

    /**
     * Get the time (as epoch int) that this Availability ends.
     * Returns null if the Availability does not end.
     *
     * @return int
     */
    public function getEffectiveTo()
    {
        return $this->effective_to;
    }

    /**
     * Get an array of times during the Active period that the Availability is visible on the Website.
     *
     * @return array [[day: 1, start_time: 0, end_time: 86399], ...]
     */
    public function getTimeslots()
    {
        return $this->timeslots;
    }

    /**
     * Returns a MyRadioForm filled in and ripe for being used to edit this Availability.
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
                ]
            );
    }

    /**
     * Return if this Availability is currently active. That is, it has started and has not expired.
     * It returns true even when there isn't currently a Timeslot for the Availaibility running.
     *
     * @return bool
     */
    public function isActive()
    {
        return $this->effective_from <= time() && ($this->effective_to == null or $this->effective_to > time());
    }

    /**
     * Removes all timeslots associated with a Availability.
     *
     * Used when editing, as they are then immediately added again.
     */
    public function clearTimeslots()
    {
        $this->timeslots = [];
        self::$db->query('DELETE FROM '.$this->timeslot_table.' WHERE '.$this->id_field.'=$1', [$this->getID()]);
        $this->updateCacheObject();
    }

    /**
     * Sets the start time of the Availability.
     *
     * @param int $time
     *
     * @return MyRadio_BannerCampaign
     */
    public function setEffectiveFrom($time)
    {
        $this->effective_from = $time;
        self::$db->query(
            'UPDATE '.$this->availability_table.' SET effective_from=$1 WHERE '.$this->id_field.'=$2',
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
                'UPDATE '.$this->availability_table.' SET effective_to=NULL WHERE '.$this->id_field.'=$1',
                [$this->getID()]
            );
        } else {
            self::$db->query(
                'UPDATE '.$this->availability_table.' SET effective_to=$1 WHERE '.$this->id_field.'=$2',
                [CoreUtils::getTimestamp($time), $this->getID()]
            );
        }

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
            'INSERT INTO '.$this->timeslot_table.'
            ('.$this->id_field.', memberid, approvedid, day, start_time, end_time)
            VALUES ($1, $2, $2, $3, $4, $5) RETURNING id',
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
     * Get all Banner Campaigns.
     *
     * @return MyRadio_BannerCampaign[]
     */
    public static function getAllAvailabilities()
    {
        return self::resultSetToObjArray(
            self::$db->fetchColumn(
                'SELECT '.$this->id_field.' FROM '.$this->availability_table
            )
        );
    }

    /**
     * Returns the form needed to create or edit Availabilities.
     *
     * @return MyRadioForm
     */
    protected static function getForm($module, $action)
    {
        return (
            new MyRadioForm(
                'availabilityfrm',
                $module,
                $action
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
                    'explanation' => 'The time from which this Availability becomes active.',
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
                    'explanation' => 'The time at which this Availability becomes inactive. Leaving this blank means'
                    .' the Availability will continue indefinitely.',
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
                    .' week that this Availability is considered active, and therefore appears on the website.'
                    .' Click a square to toggle it. Click and drag to select lots at once!',
                ]
            )
        );
    }
}
