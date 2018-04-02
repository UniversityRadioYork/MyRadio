<?php

/**
 * Provides the MyRadio_APIKey class for MyRadio.
 */
namespace MyRadio\ServiceAPI;

use MyRadio\Iface\APICaller;

/**
 * The APIKey Class provies information and management of API Keys for the MyRadio
 * REST API.
 *
 * @uses    \Database
 */
class MyRadio_APIKey extends ServiceAPI implements APICaller
{
    use MyRadio_APICaller_Common;

    /**
     * The API Key.
     *
     * @var string
     */
    private $key;

    /**
     * Whether the API key has been revoked.
     *
     * @var bool
     */
    private $revoked;

    /**
     * Construct the API Key Object.
     *
     * @param string $key
     */
    protected function __construct($key)
    {
        $this->key = $key;
        $revoked = self::$db->fetchColumn('SELECT revoked from myury.api_key WHERE key_string=$1', [$key]);
        $this->revoked = ($revoked[0] == 't');
        $this->permissions = array_map(
            'intval',
            self::$db->fetchColumn(
                'SELECT typeid FROM myury.api_key_auth WHERE key_string=$1',
                [$key]
            )
        );
    }

    /**
     * Get the key for this apikey.
     */
    public function getID()
    {
        return $this->key;
    }
}
