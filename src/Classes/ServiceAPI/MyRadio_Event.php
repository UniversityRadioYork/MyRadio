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

 class MyRadio_Event extends ServiceAPI{
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
       * @var MyRadio_Timeslot[]
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

      public function getID(){
          return $this->event_id;
      }

      /**
       * Get the title of the event
       * @return string
       */
      public function getTitle(){
          return $this->title;
      }

      /**
       * Get the description of the event
       * @return string
       */
      public function getDescription(){
          return $this->description;
      }

      /**
       * Get the timeslots that are part of the event
       * @return MyRadio_Timeslot[]
       */
      public function getTimeslots(){
          if (!isset($this->timeslots)){
            $sql = "SELECT show_season_timeslot_id FROM schedule.event_timeslots
            WHERE event_id = $1";
            $rows = self::$db->fetchAll($sql, [$this->getID()]);
            foreach ($rows as $row){
                $this->timeslots[] = MyRadio_Timeslot::getInstance($row["show_season_timeslot_id"]);
            }
          }
          return $this->timeslots;
      }

      public function toDataSource($mixins = [])
      {
          return[
              "id" => $this->getID(),
              "title" => $this->getTitle(),
              "description" => $this->getDescription(),
              "timeslots" => $this->getTimeslots()
          ];
      }

      /**
       * Get all events
       * @return MyRadio_Event[]
       */
      public static function getAll(){
        $sql = "SELECT event_id, title, description FROM schedule.events";
        $rows = self::$db->fetchAll($sql);

        $events = [];
        foreach ($rows as $row){
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

          if (empty($result)){
              throw new MyRadioException("That Event doesn't exist.", 404);
          }

          $new_event = new self($result);
          $new_event->getTimeslots();
          return $new_event;
      }


 }