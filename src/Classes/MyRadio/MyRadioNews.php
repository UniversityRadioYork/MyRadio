<?php

/**
 * This file provides the MyRadioNews class for MyRadio
 * @package MyRadio_Core
 */

/**
 * Description of MyRadioNews
 *
 * @author Andy Durant <aj@ury.org.uk>
 * @version 20130718
 * @package MyRadio_Core
 * @uses \CacheProvider
 * @uses \Database
 * @uses \CoreUtils
 */
class MyRadioNews {
  
  public function __construct() {
    
  }
  
  /**
   * Returns the latest news item for the given feed, and if given a user, the timestamp of when they saw it
   * @param id $newsfeedid The ID of the newsfeed to check
   * @param User $user The User object to check if seen. Default null, won't return a seen column.
   * @return [[DOCUMENT]]
   * @todo Document return
   */
  public static function getNewsItem($newsfeedid, User $user = null) {
    $db = Database::getInstance();
    
    $news = $db->fetch_one('SELECT newsentryid, fname || \' \' || sname AS author, timestamp AS posted, content
      FROM public.news_feed, public.member
      WHERE public.news_feed.feedid=$1 AND public.news_feed.memberid = public.member.memberid
      AND revoked=false
      ORDER BY timestamp DESC LIMIT 1', array($newsfeedid));
    
    if (empty($news)) {
      return null;
    }
    
    return array_merge($news,
            array('seen' => $db->fetch_one('SELECT seen FROM public.member_news_feed
              WHERE newsentryid=$1 AND memberid=$2 LIMIT 1', array($news['newsentryid'], empty($user) ? 0 : $user->getID())),
                'posted' => CoreUtils::happyTime($news['posted'])
            ));
  }
  
  /**
   * @todo Document this
   * @param type $newsentryid
   * @param User $user
   */
  public static function markNewsAsRead($newsentryid, User $user) {
    $db = Database::getInstance();
    
    try {
      $db->query('INSERT INTO public.member_news_feed (newsentryid, memberid) VALUES ($1, $2)',
            array($newsentryid, $user->getID()));
    } catch (MyRadioException $e) {}; //Can sometimes get duplicate key errors
  }
}

