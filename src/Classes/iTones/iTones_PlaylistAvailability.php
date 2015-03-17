<?php

/**
 * This file provides the PlaylistAvailability class for iTones
 * @package MyRadio_iTones
 */

namespace MyRadio\iTones;

use \MyRadio\MyRadioException;
use \MyRadio\MyRadio\CoreUtils;
use \MyRadio\MyRadio\MyRadioForm;
use \MyRadio\MyRadio\MyRadioFormField;
use \MyRadio\ServiceAPI\MyRadio_User;

class iTones_PlaylistAvailability extends \MyRadio\MyRadio\MyRadio_Availability
{
    /**
     * The Playlist this is a Availability for
     * @var iTones_Playlist
     */
    private $playlist;

    /**
    * The weight of this Availability
    * @var int
    */
    private $weight;

    /**
     * Initiates the iTones_PlaylistAvailability object
     * @param int $id The ID of the PlaylistAvailability to initialise
     */
    protected function __construct($id)
    {
        $this->availability_table = 'jukebox.playlist_availability';
        $this->timeslot_table = 'jukebox.playlist_timeslot';
        $this->id_field = 'playlist_availability_id';

        $result = self::$container['database']->fetchOne(
            'SELECT * FROM ' . $this->availability_table . ' WHERE ' . $this->id_field . '=$1',
            [$id]
        );
        if (empty($result)) {
            throw new MyRadioException('Playlist Availability ' . $id . ' does not exist!');
        }

        parent::__construct($id, $result);

        $this->playlist = iTones_Playlist::getInstance($result['playlistid']);
        $this->weight = intval($result['weight']);
    }

    /**
     * Returns data about the Availability
     * @param  bool $full If true, returns full, detailed data about the timeslots in this campaign
     * @return Array
     */
    public function toDataSource($full = false)
    {
        $data = parent::toDataSource($full);
        $data['playlist'] = $this->getPlaylist()->toDataSource();
        $data['weight'] = $this->getWeight();
        $data['edit'] = [
            'display' => 'icon',
            'value' => 'pencil',
            'title' => 'Click to edit this availability',
            'url' => CoreUtils::makeURL('iTones', 'editAvailability', ['availabilityid' => $this->getID()])
        ];

        return $data;
    }

    /**
     * Get the Playlist this is a Campaign for
     * @return iTones_Playlist
     */
    public function getPlaylist()
    {
        return $this->playlist;
    }

    /**
    * Returns the weight of the Availability
    * @return int
    */
    public function getWeight()
    {
        return $this->weight;
    }

    public function setWeight($weight)
    {
        $this->weight = $weight;
        self::$container['database']->query(
            'UPDATE ' . $this->availability_table . ' SET weight=$1 WHERE ' . $this->id_field . '=$2',
            [$weight, $this->getID()]
        );
        $this->updateCacheObject();
    }

    /**
     * Returns a MyRadioForm filled in and ripe for being used to edit this Availability.
     * @return MyRadioForm
     */
    public function getEditForm()
    {
        return self::getForm($this->getPlaylist()->getID())
            ->editMode(
                $this->getID(),
                [
                    'timeslots' => $this->getTimeslots(),
                    'effective_from' => CoreUtils::happyTime($this->getEffectiveFrom()),
                    'effective_to' => $this->getEffectiveTo() === null ? null :
                        CoreUtils::happyTime($this->getEffectiveTo()),
                    'weight' => $this->getWeight()
                ]
            );
    }

    /**
     * Creates a new Availability
     * @param  iTones_Playlist $playlist       The Playlist that is being Availabled.
     * @param  int             $weight         The weight of the Availability.
     * @param  int             $effective_from Epoch time that the Availability is starts at. Default now.
     * @param  int             $effective_to   Epoch time that the Availability ends at. Default never.
     * @param  Array           $timeslots      An array of Timeslots the Availability is active during.
     * @return iTones_PlaylistAvailability The new Availability
     */
    public static function create(
        iTones_Playlist $playlist,
        $weight,
        $effective_from = null,
        $effective_to = null,
        $timeslots = []
    ) {
        if ($effective_from == null) {
            $effective_from = time();
        }

        $result = self::$container['database']->fetchColumn(
            'INSERT INTO jukebox.playlist_availability
            (playlistid, weight, effective_from, effective_to, memberid, approvedid)
            VALUES ($1, $2, $3, $4, $5, $5) RETURNING playlist_availability_id',
            [
                $playlist->getID(),
                $weight,
                CoreUtils::getTimestamp($effective_from),
                $effective_to ? CoreUtils::getTimestamp($effective_to) : null,
                MyRadio_User::getInstance()->getID()
            ]
        );

        $availability = self::getInstance($result[0]);

        foreach ($timeslots as $timeslot) {
            $availability->addTimeslot($timeslot['day'], $timeslot['start_time'], $timeslot['end_time']);
        }

        return $availability;
    }

    /**
     * Returns the form needed to create or edit Playlist Availabilities.
     *
     * @param  int $playlistid The ID of the Playlist that this Availability will be/is linked to
     * @return MyRadioForm
     */
    public static function getForm($playlistid = null)
    {
        return parent::getForm('iTones', 'editAvailability')
            ->setTitle('Edit Playlist Availability')
            ->addField(
                new MyRadioFormField(
                    'weight',
                    MyRadioFormField::TYPE_NUMBER,
                    [
                        'required' => true,
                        'label' => 'Weight',
                        'explanation' => 'A heavier playlist is more likely to be played.'
                    ]
                )
            )->addField(
                new MyRadioFormField(
                    'playlistid',
                    MyRadioFormField::TYPE_HIDDEN,
                    [
                        'value' => $playlistid
                    ]
                )
            );
    }

    public static function getAvailabilitiesForPlaylist($playlistid)
    {
        return self::resultSetToObjArray(
            self::$container['database']->fetchColumn(
                'SELECT playlist_availability_id FROM jukebox.playlist_availability WHERE playlistid=$1',
                [$playlistid]
            )
        );
    }
}
