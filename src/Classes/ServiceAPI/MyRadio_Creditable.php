<?php
/**
 * Provides the Creditable trait for MyRadio.
 */
namespace MyRadio\ServiceAPI;

use MyRadio\MyRadioException;
use MyRadio\ServiceAPI\MyRadio_User;
use MyRadio\ServiceAPI\MyRadio_Scheduler;

/**
 * The MyRadio_Creditable trait adds credits functionality to an object.
 *
 * The object obviously needs to have a credits table in the database for this
 * to work.
 *
 * @uses    \Database
 */
trait MyRadio_Creditable
{
    protected $owner;
    protected $credits = [];
    protected static $credit_names;

    /**
     * Get all credits.
     *
     * @param MyRadio_Metadata_Common $parent Used when there is inheritance enabled
     *                                        for this object. In this case credits are merged.
     *
     * @return type
     */
    public function getCredits(\MyRadio\ServiceAPI\MyRadio_Metadata_Common $parent = null)
    {
        $parent = empty($parent) ? [] : $parent->getCredits();
        $current = empty($this->credits) ? [] : $this->credits;

        return array_values(array_unique(array_merge($current, $parent), SORT_REGULAR));
    }

    /**
     * Returns an Array of Arrays containing Credit names and roles, or just
     * name.
     *
     * @param bool $types If true return an array with the role as well.
     *                    Otherwise just return the credit.
     *
     * @return type
     */
    public function getCreditsNames($types = true)
    {
        $return = [];
        foreach ($this->credits as $credits) {
            if ($types) {
                $credit['name'] = MyRadio_User::getInstance($credits['memberid'])->getName();
                $credit['type_name'] = self::getCreditName($credits['type']);
            } else {
                $credit = MyRadio_User::getInstance($credits['memberid'])->getName();
            }
            $return[] = $credit;
        }

        return $return;
    }

    /**
     * Similar to getCredits, but only returns the User objects. This means the
     * loss of the credit type in the result.
     */
    public function getCreditObjects($parent = null)
    {
        $r = [];
        foreach ($this->getCredits($parent) as $credit) {
            $r[] = $credit['User'];
        }

        return $r;
    }

    /**
     * Checks the current user is in the credits for the creditable item
     * @return boolean The user is an owner
     */
    public function isCurrentUserAnOwner()
    {
        if ($this->owner === $_SESSION['memberid']) {
            return true;
        }
        foreach ($this->getCreditObjects() as $user) {
            if ($user->getID() === $_SESSION['memberid']) {
                return true;
            }
        }

        return false;
    }

    /**
     * Gets the presenter credits for as a comma-delimited string.
     *
     * @return string
     */
    public function getPresenterString()
    {
        $credit_types = MyRadio_Scheduler::getCreditTypes();
        $credit_types_in_byline = [];
        foreach ($credit_types as $type) {
            if ($type["is_in_byline"] == "t") {
                $credit_types_in_byline[] = $type["value"];
            }
        }
        $str = '';
        foreach ($this->getCredits() as $credit) {
            if (in_array($credit['type'], $credit_types_in_byline)) {
                $str .= $credit['User']->getName().', ';
            } else {
                continue;
            }
        }

        return empty($str) ? '' : substr($str, 0, -2);
    }

    /**
     * Updates the list of Credits.
     *
     * Existing credits are kept active, ones that are not in the new list are
     * set to effective_to now, and ones that are in the new list but not exist
     * are created with effective_from now.
     *
     * @param User[] $users       An array of Users associated.
     * @param int[]  $credittypes The relevant credittypeid for each User.
     */
    public function setCredits($users, $credittypes, $table, $pkey)
    {
        //Start a transaction, atomic-like.
        self::$db->query('BEGIN');

        $newcredits = $this->mergeCreditArrays($users, $credittypes);
        $oldcredits = $this->getCredits();

        $this->removeOldCredits($oldcredits, $newcredits, $table, $pkey);
        $this->addNewCredits($oldcredits, $newcredits, $table, $pkey);
        $this->updateLocalCredits($newcredits);

        //Oh, and commit the transaction. I always forget this.
        self::$db->query('COMMIT');

        return $this;
    }

    /*
     * Merges two parallel credit arrays into one array of credits.
     *
     * @param array $users The array of incoming credit users.
     * @param array $types The array of incoming credit types.
     *
     * @return array The merged credit array.
     */
    private function mergeCreditArrays($users, $types)
    {
        return array_filter(
            array_map(
                function ($user, $type) {
                    return (empty($user) || empty($type))
                    ? null
                    : ['User' => $user, 'type' => $type, 'memberid' => $user->getID()];
                },
                $users,
                $types
            ),
            function ($credit) {
                return !empty($credit);
            }
        );
    }

    /**
     * De-activates any credits that are not in the incoming credits set.
     *
     * @param array  $old   The array of existing credits.
     * @param array  $new   The array of incoming credits.
     * @param string $table The database table to update.
     * @param string $pkey  The primary key of the object to update.
     */
    private function removeOldCredits($old, $new, $table, $pkey)
    {
        foreach ($old as $credit) {
            if (!in_array($credit, $new)) {
                self::$db->query(
                    'UPDATE '.$table.' SET effective_to=NOW()'
                    .' WHERE '.$pkey.'=$1 AND creditid=$2 AND credit_type_id=$3'
                    .' AND effective_to IS NULL',
                    [$this->getID(), $credit['User']->getID(), $credit['type']],
                    true
                );
            }
        }
    }

    /**
     * Creates any new credits that are not in the existing credits set.
     *
     * @param array  $old   The array of existing credits.
     * @param array  $new   The array of incoming credits.
     * @param string $table The database table to update.
     * @param string $pkey  The primary key of the object to update.
     */
    private function addNewCredits($old, $new, $table, $pkey)
    {
        foreach ($new as $credit) {
            //Look for an existing credit
            if (!in_array($credit, $old)) {
                // Doesn't seem to exist.
                // Double-check they're not EOL'd
                $creditedUser = MyRadio_User::getInstance($credit['memberid']);
                if ($creditedUser->getEolState() >= MyRadio_User::EOL_STATE_DEACTIVATED) {
                    $name = $creditedUser->getName();
                    throw new MyRadioException("Cannot credit $name as their profile is deactivated or archived.", 400);
                }
                self::$db->query(
                    'INSERT INTO '.$table.' ('.$pkey.', credit_type_id, creditid, effective_from,'
                    .'memberid, approvedid) VALUES ($1, $2, $3, NOW(), $4, $4)',
                    [
                        $this->getID(),
                        $credit['type'],
                        $credit['memberid'],
                        MyRadio_User::getCurrentOrSystemUser()->getID(),
                    ],
                    true
                );
            }
        }
    }

    /**
     * Updates the local credits cache for this object.
     *
     * @param array $new   The array of incoming credits
     * @param array $types The array of incoming credit types.
     */
    private function updateLocalCredits($new)
    {
        $this->credits = $new;
    }

    protected static function getCreditName($credit_id)
    {
        if (empty(self::$credit_names)) {
            $r = self::$db->fetchAll('SELECT credit_type_id, name FROM people.credit_type');

            foreach ($r as $v) {
                self::$credit_names[$v['credit_type_id']] = $v['name'];
            }
        }

        return empty(self::$credit_names[$credit_id]) ? 'Contrib' : self::$credit_names[$credit_id];
    }
}
