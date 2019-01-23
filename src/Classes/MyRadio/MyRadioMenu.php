<?php

/**
 * This file provides the MyRadioMenu class for MyRadio.
 */
namespace MyRadio\MyRadio;

use MyRadio\Config;
use MyRadio\MyRadioException;

/**
 * Abstractor for the MyRadio Menu.
 *
 * @uses    \CacheProvider
 * @uses    \Database
 * @uses    \AuthUtils
 * @uses    \URLUtils
 */
class MyRadioMenu
{
    /**
     * Returns a customised MyRadio menu for the *currently logged in* user.
     *
     * @return array A complex Menu array array array array array
     */
    public function getMenuForUser()
    {
        $full = $this->getFullMenu();

        //Iterate over the Full Menu, creating a user menu
        $menu = [];
        foreach ($full as $column) {
            $newColumn = ['title' => $column['title'], 'sections' => []];

            foreach ($column['sections'] as $section) {
                $items = [];
                foreach ($section['items'] as $item) {
                    if ($this->userHasPermission($item)) {
                        $items[] = $item;
                    }
                }
                //Add this section (if it has anything in it)
                if (!empty($items)) {
                    $newColumn['sections'][] = ['title' => $section['title'], 'items' => $items];
                }
            }

            if (!empty($newColumn['sections'])) {
                $menu[] = $newColumn;
            }
        }

        return $menu;
    }

    /**
     * Returns the entire MyRadio Main Menu structure.
     *
     * @return array An array that can be used by getMenuForUser() to build the menu
     */
    private function getFullMenu()
    {
        $data = json_decode(@file_get_contents('Menus/menu.json', FILE_USE_INCLUDE_PATH), true);

        if (is_null($data)) {
            throw new MyRadioException('Menu file not found', 500);
        } else {
            $columns = $data['columns'];
        }

        foreach ($columns as $ckey => $column) {
            foreach ($column['sections'] as $skey => $section) {
                foreach ($section['items'] as $key => $item) {
                    if (empty($item['template'])) {
                        $columns[$ckey]['sections'][$skey]['items'][$key] = array_merge(
                            $section['items'][$key],
                            $this->breakDownURL($item['url'])
                        );
                    }
                }
            }
        }

        return $columns;
    }

    /**
     * Gets all items for a module's submenu and puts them in an array.
     *
     * @param string $module The name of the module to get items for
     *
     * @return array An array that can be used by getSubMenuForUser() to build a submenu
     */
    private function getFullSubMenu($module)
    {
        $menu = json_decode(@file_get_contents('Menus/'.$module.'.json', FILE_USE_INCLUDE_PATH), true);

        if (is_null($menu)) {
            $items = [];
        } else {
            $items = $menu['menu'];

            //Get permissions for each $item
            foreach ($items as $key => $item) {
                $items[$key] = array_merge($items[$key], $this->breakDownURL($item['url']));
            }
        }

        return $items;
    }

    /**
     * Takes a $url database column entry, and breaks it into its components.
     *
     * @param string $url A database-fetched menu item URL
     *
     * @return array with four keys - 'url', 'module', 'action'. All are the String names, not IDs.
     */
    private function breakDownURL($url)
    {
        return [
            'url' => $this->parseURL($url),
            'module' => $this->parseURL($url, 'module'),
            'action' => $this->parseURL($url, 'action'),
        ];
    }

    /**
     * Check if user has permission to see this menu item.
     *
     * @param array $item A MyRadioMenu Menu Item to check permissions for. Should have been passed through
     *                    breadDownURL() previously.
     *
     * @return bool Whether the user can see this item
     */
    private function userHasPermission($item)
    {
        return empty($item['action']) or
            AuthUtils::requirePermissionAuto($item['module'], $item['action'], false);
    }

    /**
     * @todo Document
     *
     * @param type $module
     *
     * @return array
     */
    public function getSubMenuForUser($module)
    {
        $full = $this->getFullSubMenu($module);

        //Iterate over the Full Menu, creating a user menu
        $menu = [];
        foreach ($full as $item) {
            if ($this->userHasPermission($item)) {
                $menu[] = $item;
            }
        }

        return $menu;
    }

    /**
     * Detects module/action links and rewrites
     * This is a method so it can easily be changed if Apache rewrites.
     *
     * @param string $url The URL to parse
     *
     * @todo Rewrite this to make sense
     */
    private function parseURL($url, $return = 'url')
    {
        $exp = explode(',', $url);

        $module = str_replace('module=', '', $exp[0], $count);
        if ($count === 1) {
            //It can be rewritten!
            if (isset($exp[1])) {
                //An action is defined!
                $action = str_replace('action=', '', $exp[1]);
                if (isset($exp[2])) {
                    //An additional query string
                    //This could be multiple variables separated by &
                    $params = $exp[2];
                } else {
                    $params = null;
                }
            } else {
                $action = null;
                $params = null;
            }
        } else {
            //It's not a rewritable
            if ($return !== 'url') {
                return;
            }
        }
        if ($return === 'module') {
            return $module;
        } elseif ($return === 'action') {
            if (isset($exp[1])) {
                return str_replace('action=', '', $exp[1]);
            } else {
                return Config::$default_action;
            }
        }

        $url = $count === 1 ? URLUtils::makeURL($module, $action, $params) : $url;

        return $url;
    }
}
