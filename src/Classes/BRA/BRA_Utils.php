<?php

/*
 * This file provides the BRA_Utils class for MyRadio
 * @package MyRadio_BRA
 */

/**
 * This class has helper functions for communicating with a BAPS Server over BRA
 * 
 * @version 20130907
 * @author Lloyd Wallis <lpw@ury.org.uk>
 * @package MyRadio_BRA
 */
class BRA_Utils extends ServiceAPI {

  public static function getInstance($id = 0) {
    return new self();
  }
  
  public function __construct() {}
  
  public function getAllChannelInfo() {
    return json_decode(file_get_contents(Config::$bra_uri.'/channels'),true);
  }

}
