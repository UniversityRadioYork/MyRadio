<?php

/**
 * This file provides the URLUtils class for MyRadio.
 */
namespace MyRadio\MyRadio;

use MyRadio\Config;
use MyRadio\Database;
use MyRadio\MyRadioError;

/**
 * URL API Utilities.
 */
class URLUtils
{
    /**
     * Stores actionid => uri mappings of custom web addresses (e.g. /myradio/iTones/default gets mapped to /itones).
     *
     * @var array
     */
    private static $custom_uris = [];

    /**
     * Redirects back to previous page.
     */
    public static function back()
    {
        header('Location: '.$_SERVER['HTTP_REFERER']);
    }

    public static function backWithMessage($message)
    {
        header('Location: '.$_SERVER['HTTP_REFERER']
            .(strstr($_SERVER['HTTP_REFERER'], '?') !== false ? '&' : '?').'message='.base64_encode($message));
    }

    /**
     * Responds with nocontent.
     */
    public static function nocontent()
    {
        header('HTTP/1.1 204 No Content');
        exit;
    }

    /**
     * Responds with JSON data.
     */
    public static function dataToJSON($data)
    {
        header('Content-Type: application/json');
        header('HTTP/1.1 200 OK');

        //Decode to datasource if needed
        $data = CoreUtils::dataSourceParser($data);

        $canDisplayErr = Config::$display_errors || AuthUtils::hasPermission(AUTH_SHOWERRORS);
        if (!empty(MyRadioError::$php_errorlist) && $canDisplayErr) {
            $data['myradio_errors'] = MyRadioError::$php_errorlist;
        }

        echo json_encode($data, JSON_NUMERIC_CHECK | JSON_UNESCAPED_SLASHES);
        exit;
    }

    /**
     * Redirects to another page.
     *
     * @param string $module The module to which we should redirect.
     * @param string $action The optional action inside the module to target.
     * @param array  $params Additional GET variables
     */
    public static function redirect($module, $action = null, $params = [])
    {
        header('Location: '.self::makeURL($module, $action, $params));
    }

    public static function redirectWithMessage($module, $action, $message)
    {
        self::redirect($module, $action, ['message' => base64_encode($message)]);
    }

    /**
     * Builds a module/action URL.
     *
     * @param string $module
     * @param string $action
     * @param array  $params Additional GET variables
     *
     * @return string URL to Module/Action
     */
    public static function makeURL($module, $action = null, $params = [])
    {
        if (empty(self::$custom_uris) && class_exists('Database')) {
            $result = Database::getInstance()->fetchAll('SELECT actionid, custom_uri FROM myury.actions');

            foreach ($result as $row) {
                self::$custom_uris[$row['actionid']] = $row['custom_uri'];
            }
        }
        //Check if there is a custom URL configured
        $key = CoreUtils::getActionId(
            CoreUtils::getModuleId($module),
            empty($action) ? Config::$default_action : $action
        );
        if (!empty(self::$custom_uris[$key])) {
            return self::$custom_uris[$key];
        }

        if (Config::$rewrite_url) {
            $str = Config::$base_url.$module.'/'.(($action !== null) ? $action.'/' : '');
            if (!empty($params)) {
                if (is_string($params)) {
                    if (substr($params, 0, 1) !== '?') {
                        $str .= '?';
                    }
                    $str .= $params;
                } else {
                    $str .= '?';
                    foreach ($params as $k => $v) {
                        $str .= "$k=$v&";
                    }
                    $str = substr($str, 0, -1);
                }
            }
        } else {
            $str = Config::$base_url.'?module='.$module.(($action !== null) ? '&action='.$action : '');

            if (!empty($params)) {
                if (is_string($params)) {
                    $str .= "&$params";
                } else {
                    foreach ($params as $k => $v) {
                        $str .= "&$k=$v";
                    }
                }
            }
        }

        return $str;
    }
}
