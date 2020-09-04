<?php

/**
 * Provides the Event class for MyRadio
 */

namespace MyRadio\ServiceAPI;

use MyRadio\MyRadio\CoreUtils;
use MyRadio\MyRadioException;

/**
 * The MyRadio_Event class provides and stores information about timeslots grouped together into an event
 * 
 * @uses \Database
 */

class MyRadio_Event extends ServiceAPI
{
    /**
     * The ID of the Event
     * Unique name, i.e. for URL
     * @var string
     */
    private $event_id;

    /**
     * The Title of the Event
     * @var string
     */
    private $title;

    /**
     * The Description of the Event
     */
    private $description;

    /**
     * The Timeslots part of this event
     * @var int[]
     */
    private $timeslots;

    public function __construct($data)
    {
        parent::__construct();
        $this->event_id = $data["event_id"];
        $this->title = $data["title"];
        $this->description = $data["description"];
        $this->timeslots = $data["timeslots"];
    }

    public function getID()
    {
        return $this->event_id;
    }

    /**
     * Get the title of the event
     * @return string
     */
    public function getTitle()
    {
        return $this->title;
    }

    /**
     * Get the description of the event
     * @return string
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * Get the timeslots that are part of the event
     * @return MyRadio_Timeslot[]
     */
    public function getTimeslots()
    {
        if (!isset($this->timeslots)) {
            $sql = "SELECT show_season_timeslot_id FROM schedule.event_timeslots
            INNER JOIN schedule.show_season_timeslot USING (show_season_timeslot_id)
            WHERE event_id = $1
            ORDER BY start_time";
            $rows = self::$db->fetchAll($sql, [$this->getID()]);
            foreach ($rows as $row) {
                $this->timeslots[] = $row["show_season_timeslot_id"];
            }
        }
        return MyRadio_Timeslot::resultSetToObjArray($this->timeslots);
    }

    public function toDataSource($mixins = [])
    {
        return [
            "id" => $this->getID(),
            "title" => $this->getTitle(),
            "description" => $this->getDescription(),
            "timeslots" => $this->timeslots,
        ];
    }

    /**
     * Get all events
     * @return MyRadio_Event[]
     */
    public static function getAll()
    {
        $sql = "SELECT event_id, title, description FROM schedule.events";
        $rows = self::$db->fetchAll($sql);

        $events = [];
        foreach ($rows as $row) {
            $new_event = new self($row);
            $new_event->getTimeslots();
            $events[] = $new_event;
        }

        return CoreUtils::setToDataSource($events);
    }

    protected static function factory($itemid)
    {
        $sql = "SELECT event_id, title, description FROM schedule.events
          WHERE event_id = $1 LIMIT 1";
        $result = self::$db->fetchOne($sql, [$itemid]);

        if (empty($result)) {
            throw new MyRadioException("That Event doesn't exist.", 404);
        }

        $new_event = new self($result);
        $new_event->getTimeslots();
        return $new_event;
    }

    /**
     * Creates a new MyRadio_Event and returns it as an object
     * 
     * @param array $params 
     *    Required: event_id, title
     *    Optional: description
     * 
     * @return MyRadio_Event
     * 
     * @throws MyRadioException
     */

    public static function create($params = [])
    {
        // Check Required Fields
        $required = ["event_id", "title"];
        foreach ($required as $field) {
            if (!isset($params[$field])) {
                throw new MyRadioException("Parameter " . $field . "wasn't provided.", 400);
            }
        }

        // Check Unique ID
        $result = self::$db->fetchOne("SELECT COUNT(1) FROM schedule.events
          WHERE event_id = $1", [$params["event_id"]]);

        if (sizeof($result) > 0) {
            throw new MyRadioException("Event ID isn't unique.", 400);
        }

        // Add Event
        self::$db->query("INSERT INTO schedule.events
          (event_id, title, description)
           VALUES ($1, $2, $3)", [$params["event_id"], $params["title"], $params["description"]]);

        return self::getInstance($params["event_id"]);
    }

    /**
     * Add timeslot to event
     * 
     * @param $timeslotID
     * 
     * @throws MyRadioException
     */

    public function addTimeslot($timeslotID)
    {
        try {
            MyRadio_Timeslot::getInstance($timeslotID);
            if (!in_array($timeslotID, $this->timeslots)) {
                $this->timeslots[] = $timeslotID;
                self::$db->query("INSERT INTO schedule.event_timeslots
                (event_id, show_season_timeslot_id)
                VALUES ($1, $2)", [$this->getID(), $timeslotID]);
            }
        } catch (MyRadioException $e) {
            // Timeslot doesn't exist
            throw $e;
        }
    }
}
