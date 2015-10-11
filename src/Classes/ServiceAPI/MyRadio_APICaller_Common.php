<?php
/**
 * Provides the APICaller Common class for MyRadio
 * @package MyRadio_API
 */

namespace MyRadio\ServiceAPI;

/**
 * The API class is used to provide sane default methods for authenticating against the API.
 * Users of this trait should populate $this->permissions at startup.
 *
 * @package MyRadio_API
 * @uses    \Database
 */
trait MyRadio_APICaller_Common
{
    protected $permissions;

    /**
     * Getter for permissions - can be overridden by children
     * e.g. User lazy-loads these
     */
    public function getPermissions()
    {
        return $this->permissions;
    }

    /**
     * Returns if the user has the given permission.
     *
     * Always use AuthUtils::hasAuth when working with the current user.
     *
     * @param  null|int $authid The permission to test for. Null is "no permission required"
     * @return boolean Whether this user has the requested permission
     */
    public function hasAuth($authid)
    {
        return $authid === null || in_array((int)$authid, $this->getPermissions());
    }

    /**
     * Returns if the user can call this method via the REST API
     */
    public function canCall($class, $method)
    {
        // I am become superuser, doer of API calls
        if ($this->hasAuth(AUTH_APISUDO)) {
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
            if ($this->hasAuth($type)) {
                return true; //The Key has that permission
            }
        }

        return false; //Didn't match anything...
    }

     /**
     * Tells you whether this APICaller can use the given mixins.
     *
     * @param  String   $class  The class the method belongs to (actual, not API Alias)
     * @param  String[] $mixins The mixins being called
     * @return bool Whether or not the user can call this
     */
    public function canMixin($class, $mixins)
    {
        // I am become superuser, doer of API calls
        if ($this->hasAuth(AUTH_APISUDO)) {
            return true;
        }

        foreach ($mixins as $mixin) {
            $result = MyRadio_Swagger::getMixinRequirements($class, $mixin);
            if ($result === null) {
                return false; //No permissions means the method is not accessible
            }

            $ok = false;
            if (empty($result)) {
                $ok = true; //An empty array means no permissions needed
            } else {
                foreach ($result as $type) {
                    if ($this->hasAuth($type)) {
                        $ok = true; //The Key has that permission
                        break;
                    }
                }
            }

            if (!$ok) {
                return false;
            }
        }

        return true;
    }
}
