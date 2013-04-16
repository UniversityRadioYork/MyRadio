<?php
/**
 * This file provides the NIPSWeb_Token class
 * @package MyURY_NIPSWeb
 */

/**
 * The NIPSWeb_Token class
 * @todo Implement Play Token support
 * 
 * @version 17032013
 * @author Lloyd Wallis <lpw@ury.org.uk>
 * @package MyURY_NIPSWeb
 * @uses \Database
 */
class NIPSWeb_Token extends ServiceAPI {
  public static function createToken($trackid) {
    return true;
  }
  
  public static function hasToken($trackid) {
    return true;
  }
  
  public function getID() {
    return $this->id;
  }
  
  /**
   * Generate a unique session token - this is as Show Planner clients need to be more unique than a session id
   * in case the user has more than one instance of the planner open.
   * These IDs are also tied to the timeslot they were using at the time.
   * @return int a unique edit token
   */
  public static function getEditToken() {
    return (int)self::$db->fetch_column('INSERT INTO bapsplanner.client_ids (show_season_timeslot_id, session_id)
      VALUES ($1, $2) RETURNING client_id', array($_SESSION['timeslotid'], session_id()));
  }
}
