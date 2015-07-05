<?php

/**
 * Provides the MyRadio_APIKey class for MyRadio
 * @package MyRadio_API
 */

namespace MyRadio\ServiceAPI;

use \MyRadio\Iface\APICaller;

/**
 * The APIKey Class provies information and management of API Keys for the MyRadio
 * REST API.
 *
 * @package MyRadio_API
 * @uses    \Database
 */
class MyRadio_APIKey extends ServiceAPI implements APICaller
{
    use MyRadio_APICaller_Common;

    /**
     * The API Key
     * @var String
     */
    private $key;

    /**
     * Whether the API key has been revoked
     * @var bool
     */
    private $revoked;

    /**
     * Construct the API Key Object
     * @param String $key
     */
    protected function __construct($key)
    {
        $this->key = $key;
        $revoked = self::$db->fetchColumn('SELECT revoked from myury.api_key WHERE key_string=$1', [$key]);
        $this->revoked = ($revoked[0] == 't');
        $this->permissions = self::$db->fetchColumn('SELECT typeid FROM myury.api_key_auth WHERE key_string=$1', [$key]);
    }

    /**
     * Check if this API Key can call the given Method.
     *
     * @param  String $class  The class the method belongs to (actual, not API Alias)
     * @param  String $method The method being called
     * @param  boolean $ignore_revoked Don't return false if the API Key has been revoked
     * @return boolean
     */
    public function canCall($class, $method, $ignore_revoked = false)
    {
        if ($this->revoked && !$ignore_revoked) {
            return false;
        }
        
        return parent::canCall($class, $method);
    }

    /**
     * Logs that this API Key has called something. Used for auditing.
     *
     * @param      String $uri
     * @param      Array  $args
     * @deprecated
     */
    public function logCall($uri, $args)
    {
        self::$db->query(
            'INSERT INTO myury.api_key_log (key_string, remote_ip, request_path, request_params)
            VALUES ($1, $2, $3, $4)',
            [$this->key, $_SERVER['REMOTE_ADDR'], $uri, json_encode($args)]
        );
    }

    /**
     * Get the key for this apikey
     */
    public function getID()
    {
        return $this->key;
    }
}
