<?php

/*
 * Provides the Timeslot class for MyURY
 * @package MyURY_Scheduler
 */

/*
 * The Timeslot class is used to view and manupulate Timeslot within the new MyURY Scheduler Format
 * @todo Generally the creation of bulk Timeslots is currently handled by the Season/Show classes, but this should change
 * @version 26122012
 * @author Lloyd Wallis <lpw@ury.org.uk>
 * @package MyURY_Scheduler
 * @uses \Database
 * @uses \MyURY_Show
 * 
 */

class MyURY_Timeslot extends MyURY_Scheduler_Common {

  private static $timeslots = array();
  private $timeslot_id;
  private $start_time;
  private $duration;
  private $season_id;
  private $owner;
  private $timeslot_num;
  private $metadata;

  public static function getInstance($timeslot_id = null) {
    if (!is_numeric($timeslot_id)) {
      throw new MyURYException('Invalid Timeslot ID!', MyURYException::FATAL);
    }

    if (!isset(self::$timeslots[$timeslot_id])) {
      self::$timeslots[$timeslot_id] = new self($timeslot_id);
    }

    return self::$timeslots[$timeslot_id];
  }

  private function __construct($timeslot_id) {
    $this->timeslot_id = $timeslot_id;
    //Init Database
    self::initDB();

    //Get the basic info about the season
    $result = self::$db->fetch_one('SELECT show_season_timeslot_id, show_season_id, start_time, duration, memberid,
      (SELECT array(SELECT metadata_key_id FROM schedule.timeslot_metadata WHERE show_season_timeslot_id=$1 AND effective_from <= NOW()
        ORDER BY effective_from, show_season_timeslot_id)) AS metadata_types,
      (SELECT array(SELECT metadata_value FROM schedule.timeslot_metadata WHERE show_season_timeslot_id=$1 AND effective_from <= NOW()
        ORDER BY effective_from, show_season_timeslot_id)) AS metadata,
      (SELECT COUNT(*) FROM schedule.show_season_timeslot
        WHERE show_season_id=(SELECT show_season_id FROM schedule.show_season_timeslot WHERE show_season_timeslot_id=$1)
        AND start_time<=(SELECT start_time FROM schedule.show_season_timeslot WHERE show_season_timeslot_id=$1))
      AS timeslot_num
      FROM schedule.show_season_timeslot WHERE show_season_timeslot_id=$1', array($timeslot_id));
    if (empty($result)) {
      //Invalid Season
      throw new MyURYException('The MyURY_Timeslot with instance ID #' . $timeslot_id . ' does not exist.');
    }

    //Deal with the easy bits
    $this->timeslot_id = (int) $result['show_season_timeslot_id'];
    $this->season_id = (int) $result['show_season_id'];
    $this->start_time = strtotime($result['start_time']);
    $this->duration = $result['duration'];
    $this->owner = User::getInstance($result['memberid']);
    $this->timeslot_num = (int) $result['timeslot_num'];

    $metadata_types = self::$db->decodeArray($result['metadata_types']);
    $metadata = self::$db->decodeArray($result['metadata']);
    //Deal with the metadata
    for ($i = 0; $i < sizeof($metadata_types); $i++) {
      if (self::isMetadataMultiple($metadata_types[$i])) {
        $this->metadata[$metadata_types[$i]][] = $metadata[$i];
      } else {
        $this->metadata[$metadata_types[$i]] = $metadata[$i];
      }
    }
  }

  public function getMeta($meta_string) {
    $key = self::getMetadataKey($meta_string);
    if (isset($this->meta[$key])) {
      return $this->meta[$key];
    } else {
      return $this->getSeason()->getMeta($meta_string);
    }
  }

  public function getID() {
    return $this->timeslot_id;
  }

  public function getSeason() {
    return MyURY_Season::getInstance($this->season_id);
  }

  public function getWebpage() {
    $season = $this->getSeason();
    return 'http://ury.org.uk/show/' . $season->getShow()->getID() . '/' . $season->getSeasonNumber().'/'.$this->getTimeslotNumber();
  }
  
  /**
   * Get the Timeslot number - for the first Timeslot of a Season, this is 1, for the second it's 2 etc.
   * @return int
   */
  public function getTimeslotNumber() {
    return $this->timeslot_num;
  }
  
  public function getStartTime() {
    return $this->start_time;
  }
  
  public function getDuration() {
    return $this->duration;
  }

  public function toDataSource() {
    return array_merge($this->getSeason()->toDataSource(), array(
                'id' => $this->getID(),
                'timeslot_num' => $this->getTimeslotNumber(),
                'title' => $this->getMeta('title'),
                'description' => $this->getMeta('description'),
                'start_time' => CoreUtils::happyTime($this->getStartTime()),
                'duration' => $this->getDuration()
            ));
  }

  /**
   * Deletes this Timeslot from the Schedule, and everything associated with it
   * @todo Make the smarter - check if it's a programming team person, in which case just do this, if it's not
   *       then if >48hrs away just do it but email programming, but <48hrs should hide it but tell prog to confirm reason
   */
  public function cancelTimeslot() {

    $email = 'Please note that an episode your show, ' . $this->getMeta('title') . ' has been cancelled for the rest of the current Season. The affected episode was at '.$this->getStartTime();
    $email .= "\r\n\r\nRegards\r\nURY Programming Team";

    foreach ($this->getShow()->getCredits() as $credit) {
      $u = User::getInstance($credit);
      MyURYEmail::sendEmail($u->getName() . ' <' . $u->getEmail() . '>', 'Episode of '.$this->getMeta('title').' Cancelled', $email);
    }

    $r = (bool) self::$db->query('DELETE FROM schedule.show_season_timeslot WHERE show_season_timeslot_id=$1', array($this->getID()));

    /**
     * @todo This is massively overkill, isn't it?
     */
    $m = new Memcached();
    $m->addServer(Config::$django_cache_server, 11211);
    $m->flush();

    return $r;
  }

}
