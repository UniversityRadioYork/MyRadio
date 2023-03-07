<?php

namespace MyRadio;

use MyRadio\Iface\CacheProvider;
use MyRadio\MyRadio\AuthUtils;

/**
 * The DebugCacheProvider wraps another CacheProvider and logs all its accesses.
 */
class DebugCacheProvider implements Iface\CacheProvider
{
    private CacheProvider $wrapped;
    private $actions = [];

    /**
     * @param CacheProvider $wrapped
     */
    public function __construct(CacheProvider $wrapped)
    {
        $this->wrapped = $wrapped;
    }

    /**
     * @inheritDoc
     */
    public function set($key, $value, $expires = 0)
    {
        $this->wrapped->set($key, $value, $expires);
        $this->actions[] = [
            'type' => 'set',
            'key' => $key,
            'value' => $value,
            'expires' => $expires
        ];
    }

    /**
     * @inheritDoc
     */
    public function get($key)
    {
        $result = $this->wrapped->get($key);
        $this->actions[] = [
            'type' => 'get',
            'key' => $key,
            'result' => $result
        ];
        return $result;
    }

    /**
     * @inheritDoc
     */
    public function getAll($keys)
    {
        $result = $this->wrapped->getAll($keys);
        $this->actions[] = [
            'type' => 'getAll',
            'keys' => $keys,
            'result' => $result
        ];
        return $result;
    }

    /**
     * @inheritDoc
     */
    public function delete($key)
    {
        $result = $this->wrapped->delete($key);
        $this->actions[] = [
            'type' => 'getAll',
            'key' => $key,
            'result' => $result
        ];
        return $result;
    }

    /**
     * @inheritDoc
     */
    public function purge()
    {
        $result = $this->wrapped->purge();
        $this->actions[] = [
            'type' => 'purge',
            'result' => $result
        ];
        return $result;
    }

    public function getActions() {
        AuthUtils::requirePermission(AUTH_IMPERSONATE);
        return $this->actions;
    }

    public static function getInstance()
    {
        throw new MyRadioException('getInstance not implemented for DebugCacheProvider!');
    }
}