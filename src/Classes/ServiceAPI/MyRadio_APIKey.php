<?php

/**
 * Provides the MyRadio_APIKey class for MyRadio
 * @package MyRadio_API
 */

namespace MyRadio\ServiceAPI;

use \MyRadio\ServiceAPI\MyRadio_Swagger;

/**
 * The APIKey Class provies information and management of API Keys for the MyRadio
 * REST API.
 *
 * @package MyRadio_API
 * @uses    \Database
 */
class MyRadio_APIKey extends ServiceAPI
{
    /**
     * The API Key
     * @var String
     */
    private $key;

    /**
     * The Permission flags this API key has.
     * @var int[]
     */
    private $permissions;

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
     * @return boolean
     */
    public function canCall($class, $method, $ignore_revoked = false)
    {
        if ($this->revoked && !$ignore_revoked) {
            return false;
        }
        if (in_array(AUTH_APISUDO, $this->permissions)) {
            return true;
        }

        $result = MyRadio_Swagger::getCallRequirements($class, $method);

        if ($result === null) {
            return false; //No permissions means the method is not accessible
        }

        if (empty($result)) {
            return true; //An empty array means no permissions needed
        }

        foreach ($result as $type) {
            if (in_array($type, $this->permissions)) {
                return true; //The Key has that permission
            }
        }

        return false; //Didn't match anything...
    }

    /**
     * Logs that this API Key has called something. Used for auditing.
     *
     * @param      String $uri
     * @param      Array  $args
     * @deprecated
     * @todo       A better way of doing this
     * @todo       Disabled as user auth checking would log passwords
     */
    public function logCall($uri, $args)
    {
        return;
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
