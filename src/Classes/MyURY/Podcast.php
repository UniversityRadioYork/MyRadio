<?php

/**
 * Abstractor for the Podcast Module
 * 
 * @depends Cache
 * @depends Database
 * @author Andy Durant <aj@ury.york.ac.uk>
 */
class Podcast {
    
  private $cache;

  public function __construct() {
    $obj = Config::$cache_provider;
    $this->cache = $obj::getInstance();
  }

  /**
   * Returns the Show Linked Podcasts for the given user
   * @param User $user
   * @return Array A complex Podcast array array array array array
   */
  public function getShowLinkedPodcastsForUser(User $user) {
    //Check if it's cached
    $cache = $this->cache->get('Podcast_ShowLinked_' . $user->getID());
    if ($cache !== false)
      return $cache;

    //Okay, it isn't cached. Maybe at least the podcast result set is
    $full = $this->getAllShowLinkedPodcasts();

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
      $this->cache->set('Podcast_ShowLinked_' . $user->getID(), $showlinked, 3600);
    }

    return $showlinked;
  }
  /**
   * Returns the entire MyURY Show Linked Podcast list 
   */
  public function getAllShowLinkedPodcasts() {
    $showlinked = $this->cache->get('Podcast_ShowLinked_Full');
    if ($showlinked === false) {
      //It's not cached. Let's generate it now
      $db = Database::getInstance();
      //First, shows
      $shows = $db->fetch_all('SELECT podid, title FROM public.pod_item
        ORDER BY position ASC');
      //Now, sections
      $sections = $db->fetch_all('SELECT sectionid, columnid, title FROM myury.menu_sections
        ORDER BY position ASC');
      //And finally, items
      $items = array_merge(
              $db->fetch_all('SELECT itemid, sectionid, title, url, description FROM myury.menu_links ORDER BY title ASC'),
              $db->fetch_all('SELECT sectionid, template FROM myury.menu_twigitems')
      );
      //Get permissions for each $item
      foreach ($items as $key => $item) {
        if (!isset($item['itemid']))
          continue; //Skip twigitems
        $items[$key]['permissions'] = $db->fetch_column('SELECT typeid FROM myury.menu_auth
          WHERE linkid=$1', array($item['itemid']));
        $items[$key]['url'] = $this->parseURL($item['url']);
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
          $newItems = array();
          //Iterate over each item
          foreach ($items as $item) {
            if ($item['sectionid'] != $section['sectionid'])
              continue;
            //Item is for this section
            $newItems[] = $item;
          }
          $newShow['sections'][] = array('title' => $section['title'], 'items' => $newItems);
        }

        $showlinked[] = $newShow;
      }
      //Cache for a long, long while
      $this->cache->set('Podcast_ShowLinked_Full', $showlinked);
    }
    return $showlinked;
  }
}