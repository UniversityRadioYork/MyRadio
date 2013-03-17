<?php
/**
 * This file provides the NIPSWeb_PlayToken class
 * @package MyURY_NIPSWeb
 */

/**
 * The NIPSWeb_PlayToken class
 * 
 * @version 17032013
 * @author Lloyd Wallis <lpw@ury.org.uk>
 * @package MyURY_NIPSWeb
 * @uses \Database
 */
class NIPSWeb_PlayToken extends ServiceAPI {
  public static function createToken($trackid) {
    return true;
  }
  
  public static function hasToken($trackid) {
    return true;
  }
}
