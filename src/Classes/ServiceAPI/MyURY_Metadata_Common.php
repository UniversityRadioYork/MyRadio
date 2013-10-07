<?php

/**
 * Provides the Metadata Common class for MyURY
 * @package MyURY_Core
 */

/**
 * The Metadata_Common class is used to provide common resources for
 * URY assets that utilise the Metadata system.
 * 
 * The metadata system is a used to attach common attributes to an item,
 * such as a title or description. It includes versioning in the form of
 * effective_from and effective_to field, storing a history of previous values.
 * 
 * @version 20130815
 * @author Lloyd Wallis <lpw@ury.org.uk>
 * @package MyURY_Scheduler
 * @uses \Database
 * 
 */
abstract class MyURY_Metadata_Common extends ServiceAPI {

  protected static $metadata_keys = array();
  protected $metadata;
  protected $credits;
  protected static $credit_names;

  /**
   * Gets the id for the string representation of a type of metadata
   */
  public static function getMetadataKey($string) {
    self::cacheMetadataKeys();
    if (!isset(self::$metadata_keys[$string])) {
      throw new MyURYException('Metadata Key ' . $string . ' does not exist');
    }
    return self::$metadata_keys[$string]['id'];
  }

  /**
   * Gets whether the type of metadata is allowed to exist more than once
   */
  public static function isMetadataMultiple($id) {
    self::cacheMetadataKeys();
    foreach (self::$metadata_keys as $key) {
      if ($key['id'] == $id) {
        return $key['multiple'];
      }
    }
    throw new MyURYException('Metadata Key ID ' . $id . ' does not exist');
  }

  protected static function cacheMetadataKeys() {
    if (empty(self::$metadata_keys)) {
      self::initDB();
      $r = self::$db->fetch_all('SELECT metadata_key_id AS id, name,'
              . ' allow_multiple AS multiple FROM metadata.metadata_key');
      foreach ($r as $key) {
        self::$metadata_keys[$key['name']]['id'] = (int) $key['id'];
        self::$metadata_keys[$key['name']]['multiple'] = ($key['multiple'] === 't');
      }
    }
  }

  protected static function getCreditName($credit_id) {
    if (empty(self::$credit_names)) {
      $r = self::$db->fetch_all('SELECT credit_type_id, name FROM people.credit_type');

      foreach ($r as $v) {
        self::$credit_names[$v['credit_type_id']] = $v['name'];
      }
    }

    return empty(self::$credit_names[$credit_id]) ? 'Contrib' : self::$credit_names[$credit_id];
  }

  /**
   * @todo This is a duplication of CoreUtils functionality.
   * @deprecated
   */
  protected static function formatTimeHuman($time) {
    date_default_timezone_set('UTC');
    $stime = date(' H:i', $time['start_time']);
    $etime = date('H:i', $time['start_time'] + $time['duration']);
    date_default_timezone_set('Europe/London');
    return self::getDayNameFromID($time['day']) . $stime . ' - ' . $etime;
  }

  /**
   * @todo Move this into CoreUtils.
   */
  protected static function getDayNameFromID($day) {
    switch ($day) {
      case 0:
        return 'Mon';
      case 1:
        return 'Tue';
      case 2:
        return 'Wed';
      case 3:
        return 'Thu';
      case 4:
        return 'Fri';
      case 5:
        return 'Sat';
      case 6:
        return 'Sun';
      default:
        throw new MyURYException('Invalid Day ID ' . $day);
    }
  }

  /**
   * 
   * @param int $term_id The term to check for
   * @param Array $time:
   * day: The day ID (0-6) to check for
   * start_time: The start time in seconds since midnight
   * duration: The duration in seconds
   * 
   * Return: Array of conflicts with week # as key and show as value
   * 
   * @todo Move this into the relevant scheduler class
   */
  protected static function getScheduleConflicts($term_id, $time) {
    self::initDB();
    $conflicts = array();
    $date = MyURY_Scheduler::getTermStartDate($term_id);
    //Iterate over each week
    for ($i = 1; $i <= 10; $i++) {
      //Get the start and end times
      $start = $date + $time['start_time'];
      $end = $date + $time['start_time'] + $time['duration'];
      //Query for conflicts
      $r = self::getScheduleConflict($start, $end);

      //If there's a conflict, log it
      if (!empty($r)) {
        $conflicts[$i] = $r['show_season_id'];
      }

      //Increment week
      $date += 3600 * 24 * 7;
    }
    return $conflicts;
  }

  /**
   * Returns a schedule conflict between the given times, if one exists
   * @param int $start Start time
   * @param int $end End time
   * @return Array empty if no conflict, show information otherwise
   * 
   * @todo Move this into the relevant scheduler class
   */
  protected static function getScheduleConflict($start, $end) {
    $start = CoreUtils::getTimestamp($start);
    $end = CoreUtils::getTimestamp($end-1);
    print_r($start);print_r($end);

    return self::$db->fetch_one('SELECT show_season_timeslot_id,
        show_season_id, start_time, start_time+duration AS end_time,
        \'$1\' AS requested_start, \'$2\' AS requested_end
        FROM schedule.show_season_timeslot
        WHERE (start_time <= $1 AND start_time + duration > $1)
        OR (start_time > $1 AND start_time < $2)', array($start, $end));
  }

  /**
   * Returns the Term currently available for Season applications.
   * Users can only apply to the current term, or one week before the next one
   * starts.
   * 
   * @return int|null Returns the id of the term or null if no active term
   * 
   * @todo Move this into the relevant scheduler class or CoreUtils
   */
  public static function getActiveApplicationTerm() {
    $return = self::$db->fetch_column('SELECT termid FROM terms
      WHERE start <= $1 AND finish >= NOW() LIMIT 1', array(CoreUtils::getTimestamp(strtotime('+28 Days'))));
    return $return[0];
  }

  /**
   * Sets a *text* metadata key to the specified value. Does not work for image metadata.
   * 
   * If any value is the same as an existing one, no action will be taken.
   * If the given key has is_multiple, then the value will be added as a new, additional key.
   * If the key does not have is_multiple, then any existing values will have effective_to
   * set to the effective_from of this value, effectively replacing the existing value.
   * This will *not* unset is_multiple values that are not in the new set.
   * 
   * @param String $string_key The metadata key
   * @param mixed $value The metadata value. If key is_multiple and value is an array, will create instance
   * for value in the array.
   * @param int $effective_from UTC Time the metavalue is effective from. Default now.
   * @param int $effective_to UTC Time the metadata value is effective to. Default NULL (does not expire).
   * @param String $table The metadata table, *including* the schema.
   * @param String $id_field The ID field in the metadata table.
   */
  public function setMeta($string_key, $value, $effective_from = null, $effective_to = null, $table = null, $id_field = null) {
    $meta_id = self::getMetadataKey($string_key); //Integer meta key
    $multiple = self::isMetadataMultiple($meta_id); //Bool whether multiple values are allowed
    if ($effective_from === null) {
      $effective_from = time();
    }

    //Check if value is different
    if ($multiple) {
      if (is_array($value)) {
        foreach ($value as $k => $v) {
          if (in_array($v, $this->getMeta($string_key)) !== false) {
            //This is a pre-existing value
            unset($value[$k]);
          }
        }
        if (empty($value)) {
          //Nothing's changed
          return false;
        }
      } else {
        if (in_array($value, $this->getMeta($string_key)) !== false) {
          //This is a pre-existing value
          return false;
        }
      }
    } else {
      //Not multiple key
      if (is_array($value)) {
        //Can't have an array for a single value
        throw new MyURYException('Tried to set multiple values for a single-instance metadata key!');
      }
      if ($value == $this->getMeta($string_key)) {
        //Value not changed
        return false;
      }
      //Okay, expire old value.
      self::$db->query('UPDATE ' . $table . ' SET effective_to = $1
        WHERE metadata_key_id=$2 AND ' . $id_field . '=$3', array(CoreUtils::getTimestamp($effective_from), $meta_id, $this->getID()));
    }

    $sql = 'INSERT INTO ' . $table
            . ' (metadata_key_id, ' . $id_field . ', memberid, approvedid, metadata_value, effective_from, effective_to) VALUES ';
    $params = array($meta_id, $this->getID(), User::getInstance()->getID(), CoreUtils::getTimestamp($effective_from),
        $effective_to == null ? null : CoreUtils::getTimestamp($effective_to));

    if (is_array($value)) {
      $param_counter = 6;
      foreach ($value as $v) {
        $sql .= '($1, $2, $3, $3, $' . $param_counter . ', $4, $5),';
        $params[] = $v;
        $param_counter++;
      }
      //Remove the extra comma
      $sql = substr($sql, 0, -1);
    } else {
      $sql .= '($1, $2, $3, $3, $6, $4, $5)';
      $params[] = $value;
    }

    self::$db->query($sql, $params);

    if ($multiple && is_array($value)) {
      foreach ($value as $v) {
        if (!in_array($v, $this->metadata[$meta_id])) {
          $this->metadata[$meta_id][] = $v;
        }
      }
    } else {
      $this->metadata[$meta_id] = $value;
    }

    return true;
  }

  /**
   * Returns an Array of Arrays containing Credit names and roles, or just name.
   * @param boolean $types If true return an array with the role as well. Otherwise just return the credit.
   * @return type
   */
  public function getCreditsNames($types = true) {
    $return = array();
    foreach ($this->credits as $credit) {
      if ($types) {
        $credit['name'] = User::getInstance($credit['memberid'])->getName();
        $credit['type_name'] = self::getCreditName($credit['type']);
      } else {
        $credit = User::getInstance($credit['memberid'])->getName();
      }
      $return[] = $credit;
    }
    return $return;
  }

  /**
   * Get all credits
   * @param MyURY_Metadata_Common $parent Used when there is inheritance enabled
   * for this object. In this case credits are merged.
   * @return type
   */
  public function getCredits($parent = null) {
    $parent = $parent === null ? [] : $parent->getCredits();
    return array_unique(array_merge($this->credits, $parent), SORT_REGULAR);
  }

  /**
   * Similar to getCredits, but only returns the User objects. This means the loss of the credit type in the result.
   */
  public function getCreditObjects($parent = null) {
    $r = array();
    foreach ($this->getCredits($parent) as $credit) {
      $r[] = $credit['User'];
    }
    return $r;
  }

  /**
   * Gets the presenter credits for as a comma-delimited string.
   * 
   * @return String
   */
  public function getPresenterString() {
    $str = '';
    foreach ($this->getCredits() as $credit) {
      if ($credit['type'] !== 1) {
        continue;
      } else {
        $str .= $credit['User']->getName().', ';
      }
    }
    
    return substr($str, 0, -2);
  }
  
  public function getMeta($meta_string) {
    return isset($this->metadata[self::getMetadataKey($meta_string)]) ?
      $this->metadata[self::getMetadataKey($meta_string)] : null;
  }
  
  /**
   * Updates the list of Credits.
   * 
   * Existing credits are kept active, ones that are not in the new list are set to effective_to now,
   * and ones that are in the new list but not exist are created with effective_from now.
   * 
   * @param User[] $users An array of Users associated.
   * @param int[] $credittypes The relevant credittypeid for each User.
   */
  public function setCredits($users, $credittypes, $table, $pkey) {
    //Start a transaction, atomic-like.
    self::$db->query('BEGIN');
    
    $oldcredits = $this->getCredits();
    //Remove old credits
    foreach ($oldcredits as $credit) {
      if (empty($credit['User'])) {
        continue;
      }
      if (!(($key = array_search($credit['User']->getID(),
              array_map(function($x){return $x->getID();}, $users))) === false
              && $credit['type'] == $credittypes[$key])) {
        //There's not a match for this. Remove it
        self::$db->query('UPDATE '.$table.' SET effective_to=NOW()'
                . 'WHERE '.$pkey.'=$1 AND creditid=$2 AND credit_type_id=$3',
                [$this->getID(), $credit['User']->getID(), $credit['type']],
                true);
      }
    }
    
    //Add new credits
    for ($i = 0; $i < sizeof($users); $i++) {
      if (empty($users[$i]) or empty($credittypes[$i])) {
        continue;
      }
      
      //Look for an existing credit
      if (!in_array(['type' => $credittypes[$i], 'memberid' => $users[$i]->getID(), 'User' => $users[$i]], 
              $oldcredits)) {
        //Doesn't seem to exist.
        self::$db->query('INSERT INTO '.$table.' ('.$pkey.', credit_type_id, creditid, effective_from,'
                . 'memberid, approvedid) VALUES ($1, $2, $3, NOW(), $4, $4)', [
                    $this->getID(), $credittypes[$i], $users[$i]->getID(), User::getInstance()->getID()
                ], true);
      }
    }
    
    //Cool. Update the local credits data
    $newcredits = [];
    for ($i = 0; $i < sizeof($users); $i++) {
      if (empty($users[$i])) {
        continue;
      }
      $newcredits[] = ['type' => $credittypes[$i], 'memberid' => $users[$i]->getID(), 'User' => $users[$i]];
    }
    
    $this->credits = $newcredits;
    
    //Oh, and commit the transaction. I always forget this.
    self::$db->query('COMMIT');
    
    return $this;
  }

}
