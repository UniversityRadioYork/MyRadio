<?php
/**
 * This file provides the NIPSWeb_TimeslotItem class for MyRadio - a Show Plan wrapper for all items
 * @package MyRadio_NIPSWeb
 */

/**
 * The NIPSWeb_TimeslotItem class helps provide Show Planner with access to all resource types a timeslot item could be
 *
 * @version 16042013
 * @author Lloyd Wallis <lpw@ury.org.uk>
 * @package MyRadio_NIPSWeb
 * @uses \Database
 */
class NIPSWeb_TimeslotItem extends ServiceAPI
{
    private $timeslot_item_id;

    private $item;

    private $channel;

    private $weight;

    /**
     * Initiates the TimeslotItem variables
     * @param int $resid The timeslot_item_id of the resource to initialise
     * @param NIPSWeb_ManagedPlaylist $playlistref If the playlist is requesting this item, then pass the playlist object
     */
    protected function __construct($resid, $playlistref = null)
    {
        $this->timeslot_item_id = $resid;
        //*dies*
        $result = self::$db->fetchOne(
            'SELECT * FROM bapsplanner.timeslot_items where timeslot_item_id=$1 LIMIT 1',
            array($resid)
        );

        if (empty($result)) {
            throw new MyRadioException('The specified Timeslot Item does not seem to exist');

            return;
        }

        /**
        * @todo detect definition of multiple track types in an entry and fail out
        */
        if ($result['rec_track_id'] != null) {
            //CentralDB
            $this->item = MyRadio_Track::getInstance($result['rec_track_id']);
        } elseif ($result['managed_item_id'] != null) {
            //ManagedDB (Central Beds, Jingles...)
            $this->item = NIPSWeb_ManagedItem::getInstance($result['managed_item_id'], $playlistref);
        }

        $this->channel = (int) $result['channel_id'];
        $this->weight = (int) $result['weight'];
    }

    /**
     * Get the unique timeslotitemid of the TimeslotItem
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

    public function getItem()
    {
        return $this->item;
    }

    public function setLocation($channel, $weight)
    {
        $this->channel = (int) $channel;
        $this->weight = (int) $weight;
        self::$db->query(
            'UPDATE bapsplanner.timeslot_items SET channel_id=$1, weight=$2 WHERE timeslot_item_id=$3',
            array($this->channel, $this->weight, $this->getID())
        );
        $this->updateCacheObject();
    }

    public function remove()
    {
        self::$db->query(
            'DELETE FROM bapsplanner.timeslot_items WHERE timeslot_item_id=$1',
            array($this->getID())
        );
        $this->removeInstance();
        unset($this);
    }

    public static function createManaged($timeslot, $manageditemid, $channel, $weight)
    {
        $result = self::$db->fetchColumn(
            'INSERT INTO bapsplanner.timeslot_items (timeslot_id, managed_item_id, channel_id, weight)
            VALUES ($1, $2, $3, $4) RETURNING timeslot_item_id',
            array($timeslot, $manageditemid, $channel, $weight)
        );

        return self::getInstance($result[0]);
    }

    public static function createCentral($timeslot, $trackid, $channel, $weight)
    {
        $result = self::$db->fetchColumn(
            'INSERT INTO bapsplanner.timeslot_items (timeslot_id, rec_track_id, channel_id, weight)
            VALUES ($1, $2, $3, $4) RETURNING timeslot_item_id',
            array($timeslot, $trackid, $channel, $weight)
        );

        return self::getInstance($result[0]);
    }

    /**
     * Returns an array of key information, useful for Twig rendering and JSON requests
     * @todo Expand the information this returns
     * @return Array
     */
    public function toDataSource()
    {
        return array_merge(
            array(
                'timeslotitemid' => $this->getID(),
                'channel' => $this->getChannel(),
                'weight' => $this->getWeight()
            ),
            $this->getItem()->toDataSource()
        );
    }
}
