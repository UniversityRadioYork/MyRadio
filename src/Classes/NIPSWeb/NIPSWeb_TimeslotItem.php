<?php
/**
 * This file provides the NIPSWeb_TimeslotItem class for MyRadio - a Show Plan wrapper for all items.
 */
namespace MyRadio\NIPSWeb;

use MyRadio\MyRadioException;
use MyRadio\ServiceAPI\MyRadio_Track;

/**
 * The NIPSWeb_TimeslotItem class helps provide Show Planner with access to all resource types a timeslot item could be.
 *
 * @uses    \Database
 */
class NIPSWeb_TimeslotItem extends \MyRadio\ServiceAPI\ServiceAPI
{
    private $timeslot_item_id;

    private $item_id;

    private $item_type;

    private $item_playlist_ref;

    private $channel;

    private $weight;

    private $cue;

    /**
     * Initiates the TimeslotItem variables.
     *
     * @param int $resid The timeslot_item_id of the resource to initialise
     * @param NIPSWeb_ManagedPlaylist $playlistref If the playlist is requesting this item, then pass the playlist obj
     */
    protected function __construct($resid, $playlistref = null)
    {
        $this->timeslot_item_id = $resid;
        //*dies*
        $result = self::$db->fetchOne(
            'SELECT * FROM bapsplanner.timeslot_items where timeslot_item_id=$1 LIMIT 1',
            [$resid]
        );

        if (empty($result)) {
            throw new MyRadioException('The specified Timeslot Item does not seem to exist', 404);

            return;
        }

        /*
        * @todo detect definition of multiple track types in an entry and fail out
        */
        if ($result['rec_track_id'] != null) {
            //CentralDB
            $this->item_type = "CentralDB";
            $this->item_id = $result['rec_track_id'];
        } elseif ($result['managed_item_id'] != null) {
            //ManagedDB (Central Beds, Jingles...)
            $this->item_type = "ManagedDB";
            $this->item_id = $result['managed_item_id'];
            $this->item_playlist_ref = $playlistref;
        }

        $this->channel = (int) $result['channel_id'];
        $this->weight = (int) $result['weight'];
        $this->cue = (int) $result['cue'];
    }

    /**
     * Get the unique timeslotitemid of the TimeslotItem.
     *
     * @return int
     */
    public function getID()
    {
        return $this->timeslot_item_id;
    }

    public function getChannel()
    {
        return $this->channel;
    }

    public function getWeight()
    {
        return $this->weight;
    }

    /**
     * Get the cue point of this timeslotitem, in seconds.
     */
    public function getCue()
    {
        return $this->cue;
    }

    public function getItem()
    {
        if ($this->item_type == "CentralDB") {
            return MyRadio_Track::getInstance($this->item_id);
        } elseif ($this->item_type == "ManagedDB") {
            return NIPSWeb_ManagedItem::getInstance($this->item_id, $this->item_playlist_ref);
        }
    }

    public function setLocation($channel, $weight)
    {
        $this->channel = (int) $channel;
        $this->weight = (int) $weight;
        self::$db->query(
            'UPDATE bapsplanner.timeslot_items SET channel_id=$1, weight=$2 WHERE timeslot_item_id=$3',
            [$this->channel, $this->weight, $this->getID()]
        );
        $this->updateCacheObject();
    }

    public function setCue($secs)
    {
        $this->cue = (int) $secs;
        self::$db->query(
            'UPDATE bapsplanner.timeslot_items SET cue=$1 WHERE timeslot_item_id=$2',
            [$this->cue, $this->getID()]
        );
        $this->updateCacheObject();
    }

    public function remove()
    {
        self::$db->query(
            'DELETE FROM bapsplanner.timeslot_items WHERE timeslot_item_id=$1',
            [$this->getID()]
        );
        $this->removeInstance();
    }

    public static function createManaged($timeslot, $manageditemid, $channel, $weight)
    {
        $result = self::$db->fetchColumn(
            'INSERT INTO bapsplanner.timeslot_items (timeslot_id, managed_item_id, channel_id, weight)
            VALUES ($1, $2, $3, $4) RETURNING timeslot_item_id',
            [$timeslot, $manageditemid, $channel, $weight]
        );

        return self::getInstance($result[0]);
    }

    public static function createCentral($timeslot, $trackid, $channel, $weight)
    {
        $result = self::$db->fetchColumn(
            'INSERT INTO bapsplanner.timeslot_items (timeslot_id, rec_track_id, channel_id, weight)
            VALUES ($1, $2, $3, $4) RETURNING timeslot_item_id',
            [$timeslot, $trackid, $channel, $weight]
        );

        return self::getInstance($result[0]);
    }

    /**
     * Returns an array of key information, useful for Twig rendering and JSON requests.
     * @param array $mixins Mixins. Currently unused
     * @return array
     * @todo Expand the information this returns
     */
    public function toDataSource($mixins = [])
    {
        return array_merge(
            [
                'timeslotitemid' => $this->getID(),
                'channel' => $this->getChannel(),
                'weight' => $this->getWeight(),
                'cue'   => $this->getCue()
            ],
            $this->getItem()->toDataSource()
        );
    }
}
