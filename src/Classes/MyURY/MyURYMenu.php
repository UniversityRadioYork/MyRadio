<?php

/**
 * This file provides the MyURYMenu class for MyURY
 * @package MyURY_Core
 */

/**
 * Abstractor for the MyURY Menu
 * 
 * @author Lloyd Wallis <lpw@ury.org.uk>
 * @version 21072012
 * @package MyURY_Core
 * @uses \CacheProvider
 * @uses \Database
 * @uses \CoreUtils
 */
class MyURYMenu {

  /**
   * A reference pointer to the current MyURY CacheProvider
   * @var \CacheProvider 
   */
  private $cache;

  /**
   * Create a MyURYMenu Object
   */
  public function __construct() {
    $obj = Config::$cache_provider;
    $this->cache = $obj::getInstance();
  }

  /**
   * Returns a customised MyURY menu for the *currently logged in* user
   * @param \User $user The currently logged in User's User object
   * @return Array A complex Menu array array array array array
   */
  public function getMenuForUser(User $user) {
    //Check if it's cached
    $cache = $this->cache->get('MyURYMenu_Menu_' . $user->getID());
    if ($cache !== false)
      return $cache;

    //Okay, it isn't cached. Maybe at least the menu result set is
    $full = $this->getFullMenu();

    //Iterate over the Full Menu, creating a user menu
    $menu = array();
    foreach ($full as $column) {
      $newColumn = array('title' => $column['title'], 'sections' => array());

      foreach ($column['sections'] as $section) {
        $items = array();
        foreach ($section['items'] as $item) {
          if ($this->userHasPermission($item)) {
            $items[] = $item;
          }
        }
        //Add this section (if it has anything in it)
        if (!empty($items))
          $newColumn['sections'][] = array('title' => $section['title'], 'items' => $items);
      }

      if (!empty($newColumn['sections']))
        $menu[] = $newColumn;
      $this->cache->set('MyURYMenu_Menu_' . $user->getID(), $menu, 3600);
    }

    return $menu;
  }

  /**
   * Returns the entire MyURY Main Menu structure
   * @todo Better Documentation
   */
  private function getFullMenu() {
    $cache_key = 'MyURYMenu_Menu_Full';
    $menu = $this->cache->get($cache_key);
    if ($menu === false) {
      //It's not cached. Let's generate it now
      $db = Database::getInstance();
      //First, columns
      $columns = $db->fetch_all('SELECT columnid, title FROM myury.menu_columns
        ORDER BY position ASC');
      //Now, sections
      $sections = $db->fetch_all('SELECT sectionid, columnid, title FROM myury.menu_sections
        ORDER BY position ASC');
      //And finally, items
      $items = array_merge(
              $db->fetch_all('SELECT itemid, sectionid, title, url, description FROM myury.menu_links ORDER BY title ASC'), $db->fetch_all('SELECT sectionid, template FROM myury.menu_twigitems')
      );
      //Get permissions for each $item
      foreach ($items as $key => $item) {
        if (!isset($item['itemid']))
          continue; //Skip twigitems
        $items[$key] = array_merge($items[$key], $this->breakDownURL($item['url']));
      }

      //That'll do for now. Time to make the $menu
      $menu = array();
      foreach ($columns as $column) {
        $newColumn = array('title' => $column['title'], 'sections' => array());

        //Iterate over each section
        foreach ($sections as $section) {
          if ($section['columnid'] != $column['columnid'])
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
          $newColumn['sections'][] = array('title' => $section['title'], 'items' => $newItems);
        }

        $menu[] = $newColumn;
      }
      //Cache for a long, long while
      $this->cache->set($cache_key, $menu);
    }
    return $menu;
  }

  /**
   * Gets all items for a module's submenu and puts them in an array.
   * @param int $moduleid The id of the module to get items for
   * @return Array An array that can be used by getSubMenuForUser() to build a submenu
   * @todo Caching here breaks submenus
   */
  private function getFullSubMenu($moduleid) {
    $cache_key = 'MyURYMenu_Menu_' . $moduleid . '_' . $_SESSION['memberid'] . '_Full';
    $items = $this->cache->get($cache_key);
    if ($items === false) {
      //It's not cached. Let's generate it now
      $db = Database::getInstance();

      $items = $db->fetch_all('SELECT menumoduleid, title, url, description FROM myury.menu_module
        WHERE moduleid=$1 ORDER BY title ASC', array($moduleid));
      //Get permissions for each $item
      foreach ($items as $key => $item) {
        $items[$key] = array_merge($items[$key], $this->breakDownURL($item['url']));
      }

      //Cache for a long, long while
      $this->cache->set($cache_key, $items);
    }
    return $items;
  }

  /**
   * Takes a $url database column entry, and breaks it into its components
   * @param String $url A database-fetched menu item URL
   * @return Array with four keys - 'url', 'service', 'module', 'action'. All are the String names, not IDs.
   */
  private function breakDownURL($url) {
    return array(
        'url' => $this->parseURL($url),
        'service' => $this->parseURL($url, 'service'),
        'module' => $this->parseURL($url, 'module'),
        'action' => $this->parseURL($url, 'action')
    );
  }
  
  /**
   * Check if user has permission to see this menu item
   * @param Array $item A MyURYMenu Menu Item to check permissions for. Should have been passed through
   * breadDownURL() previously.
   * @return boolean Whether the user can see this item
   */
  private function userHasPermission($item) {
    return empty($item['action']) or
              CoreUtils::requirePermissionAuto($item['service'], $item['module'], $item['action'], false);
  }

  /**
   * @todo Document
   * @param type $moduleid
   * @param \User $user The currently logged in User's User object
   * @return array
   */
  public function getSubMenuForUser($moduleid, User $user) {
    $cache_key = 'MyURYMenu_Menu_' . $moduleid . '_' . $user->getID();
    //Check if it's cached
    $cache = $this->cache->get($cache_key);
    if ($cache !== false)
      return $cache;

    //Okay, it isn't cached. Maybe at least the menu result set is
    $full = $this->getFullSubMenu($moduleid);

    //Iterate over the Full Menu, creating a user menu
    $menu = array();
    foreach ($full as $item) {
      if ($this->userHasPermission($item)) {
        $menu[] = $item;
      }
    }
    $this->cache->set($cache_key, $menu, 3600);

    return $menu;
  }

  /**
   * Detects module/action/service links and rewrites
   * This is a method so it can easily be changed if Apache rewrites
   * @param String $url The URL to parse
   * @todo Rewrite this to make sense
   */
  private function parseURL($url, $return = 'url') {
    $exp = explode(',', $url);
    if (preg_match('/^(module|service)=/', $exp[0]) == 1) {
      //It can be rewritten!
      $url = '?' . $exp[0];
      if (isset($exp[1])) {
        //An action is defined!
        $url .= '&' . $exp[1];
        if (isset($exp[2])) {
          //An additional query string
          //This could be multiple variables separated by &
          $url .= '&' . $exp[2];
        }
      }
    } else {
      //It's not a rewritable
      if ($return !== 'url')
        return null;
    }
    if ($return === 'service') {
      if (preg_match('/^service=/', $exp[0]) == 1) {
        return str_replace('service=', '', $exp[0]);
      } else {
        return 'MyURY';
      }
    } elseif ($return === 'module') {
      if (preg_match('/^module=/', $exp[0]) == 1) {
        return str_replace('module=', '', $exp[0]);
      } else {
        return 'Core';
      }
    } elseif ($return === 'action') {
      if (isset($exp[1])) {
        return str_replace('action=', '', $exp[1]);
      } else {
        return 'default';
      }
    }
    return $url;
  }

}

