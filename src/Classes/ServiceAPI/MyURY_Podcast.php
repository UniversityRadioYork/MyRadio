<?php
/**
 * This file provides the Podcast class for MyURY
 * @package MyURY_Podcast
 */

/**
 * Abstractor for the Podcast Module
 * 
 * @uses \CacheProvider
 * @uses \Database
 * @author Andy Durant <aj@ury.org.uk>
 * @package MyURY_Podcast
 * @version 21072012
 */
class MyURY_Podcast extends ServiceAPI {

  /**
   * Returns the Show Linked Podcasts for the given user
   * @param User $user
   * @return Array A complex Podcast array array array array array
   */
  public static function getShowLinkedPodcastsForUser(User $user) {
    //Check if it's cached
    $cache = self::$cache->get('Podcast_ShowLinked_' . $user->getID());
    if ($cache !== false)
      return $cache;

    //Okay, it isn't cached. Maybe at least the podcast result set is
    $full = self::getAllShowLinkedPodcasts();

    //Iterate over the Full Podcast List, creating a user customised list
    $showlinked = array();
    foreach ($full as $column) {
      $newColumn = array('title' => $column['title'], 'sections' => array());

      foreach ($column['sections'] as $section) {
        $items = array();
        foreach ($section['items'] as $item) {
          //If permissions is empty, everyone gets it
          if (empty($item['permissions'])) {
            $items[] = $item;
            continue;
          }
          //Otherwise enumerate
          foreach ($item['permissions'] as $permission) {
            if ($user->hasAuth($permission)) {
              //Yay, add this item
              $items[] = $item;
              break;
            }
          }
        }
        //Add this section (if it has anything in it)
        if (!empty($items))
          $newColumn['sections'][] = array('title' => $section['title'], 'items' => $items);
      }

      if (!empty($newColumn['sections'])) $showlinked[] = $newColumn;
      self::$cache->set('Podcast_ShowLinked_' . $user->getID(), $showlinked, 3600);
    }

    return $showlinked;
  }
  /**
   * Returns the entire MyURY Show Linked Podcast list 
   * @todo Andy Durant needs to document the return format
   */
  public function getAllShowLinkedPodcasts() {
    $showlinked = self::$cache->get('Podcast_ShowLinked_Full');
    if ($showlinked === false) {
      //It's not cached. Let's generate it now
      //First, shows
      $shows = self::$db->fetch_all('SELECT DISTINCT summary, entryid, createddate
						FROM sched_entry
						INNER JOIN pod_item USING (entryid)
					UNION
						SELECT DISTINCT summary, entryid, createddate
						FROM sched_entry
						INNER JOIN sched_memberentry AS me USING (entryid)
						INNER JOIN pod_item USING (entryid)
					ORDER BY createddate DESC');
      //Now Podcasts for those shows
      $podcasts = self::$db->fetch_all('SELECT podid, title, extract(epoch FROM dateadded)
                                        FROM pod_item
                                    ORDER BY dateadded DESC');
      //Get permissions for each $item
      foreach ($podcasts as $key => $podcast) {
        if (!isset($podcast['podid']))
          continue; //Skip twigitems
        $podcasts[$key]['permissions'] = self::$db->fetch_column('SELECT typeid FROM myury.menu_auth
          WHERE linkid=$1', array($podcast['podid']));
        $podcasts[$key]['url'] = $this->parseURL($item['url']);
      }

      //That'll do for now. Time to make $showlinked
      $showlinked = array();
      foreach ($shows as $show) {
        $newShow = array('title' => $show['title'], 'sections' => array());
        
        //Iterate over each section
        foreach ($sections as $section) {
          if ($section['columnid'] != $show['columnid'])
            continue;
          //This section is for this column
          $newPodcasts = array();
          //Iterate over each item
          foreach ($podcasts as $podcast) {
            if ($podcast['sectionid'] != $section['sectionid'])
              continue;
            //Item is for this section
            $newPodcasts[] = $podcast;
          }
          $newShow['sections'][] = array('title' => $section['title'], 'items' => $newPodcasts);
        }

        $showlinked[] = $newShow;
      }
      //Cache for a long, long while
      self::$cache->set('Podcast_ShowLinked_Full', $showlinked);
    }
    return $showlinked;
  }
}