<?php

/**
 * Abstractor for the MyURY Menu
 * 
 * @depends Cache
 * @depends Database
 * @author Lloyd Wallis <lpw@ury.york.ac.uk>
 */
class MyURYMenu {

  private $cache;

  public function __construct() {
    $obj = Config::$cache_provider;
    $this->cache = $obj::getInstance();
  }

  public function getMenuWithPermissions($permissions) {
    $permissions_string = crc32(serialize($permissions));
    //Check if it's cached
    $cache = $this->cache->get('MyURYMenu_Menu_' . $permissions_string);
    if ($cache !== false)
      return $cache;
    //TODO: implement this...
    return array(
        array(
            'title' => 'My Stuff',
            'sections' => array(
                array(
                    'title' => 'About Me'
                ),
                array(
                    'title' => 'Get on Air'
                )
            )
        ),
        array(
            'title' => 'My Services',
            'sections' => array(
                array('title' => '',
                    'items' => array(
                        array(
                            'title' => 'Show Planner',
                            'link' => '/myury/?service=nipsweb'
                        )
                    )
                )
            )
        ),
        array(
            'title' => 'My Station'
        )
    );
  }

}
