<?php
/**
 * Provides the Creditable trait for MyRadio
 * @package MyRadio_Core
 */

/**
 * The MyRadio_Creditable trait adds credits functionality to an object.
 *
 * The object obviously needs to have a credits table in the database for this
 * to work.
 *
 * @version 20140112
 * @author Lloyd Wallis <lpw@ury.org.uk>
 * @author Matt Windsor <matt.windsor@ury.org.uk>
 * @package MyRadio_Core
 * @uses \Database
 */
trait MyRadio_Creditable {
  protected $credits = array();
  protected static $credit_names;

  /**
   * Get all credits
   * @param MyRadio_Metadata_Common $parent Used when there is inheritance enabled
   * for this object. In this case credits are merged.
   * @return type
   */
  public function getCredits($parent = null) {
    $parent = $parent === null ? [] : $parent->getCredits();
    $current = empty($this->credits) ? [] : $this->credits;
    return array_unique(array_merge($current, $parent), SORT_REGULAR);
  }

  public function getMeta($meta_string) {
    return isset($this->metadata[self::getMetadataKey($meta_string)]) ?
      $this->metadata[self::getMetadataKey($meta_string)] : null;
  }

  /**
   * Returns an Array of Arrays containing Credit names and roles, or just
   * name.
   *
   * @param boolean $types If true return an array with the role as well.
   *                       Otherwise just return the credit.
   *
   * @return type
   */
  public function getCreditsNames($types = true) {
    $return = array();
    foreach ($this->credits as $credit) {
      if ($types) {
        $credit['name'] = User::getInstance($credit['memberid'])->getName();
        $credit['type_name'] = self::getCreditName($credit['type']);
      } else {
        $credit = User::getInstance($credit['memberid'])->getName();
      }
      $return[] = $credit;
    }
    return $return;
  }

  /**
   * Similar to getCredits, but only returns the User objects. This means the
   * loss of the credit type in the result.
   */
  public function getCreditObjects($parent = null) {
    $r = array();
    foreach ($this->getCredits($parent) as $credit) {
      $r[] = $credit['User'];
    }
    return $r;
  }

  /**
   * Gets the presenter credits for as a comma-delimited string.
   *
   * @return String
   */
  public function getPresenterString() {
    $str = '';
    foreach ($this->getCredits() as $credit) {
      if ($credit['type'] !== 1) {
        continue;
      } else {
        $str .= $credit['User']->getName().', ';
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
   * @param User[] $users An array of Users associated.
   * @param int[] $credittypes The relevant credittypeid for each User.
   */
  public function setCredits($users, $credittypes, $table, $pkey) {
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
   * @param array  $users  The array of incoming credit users.
   * @param array  $types  The array of incoming credit types.
   *
   * @return array The merged credit array.
   */
  private function mergeCreditArrays($users, $types) {
    return array_filter(
      array_map(
        function($user, $type) {
          return (empty($user) || empty($type))
          ? null
          : [ 'User' => $user, 'type' => $type, 'memberid' => $user->getID() ];
        },
        $users,
        $types
      ),
      function($credit) { return !empty($credit); }
    );
  }

  /**
   * De-activates any credits that are not in the incoming credits set.
   *
   * @param array  $old    The array of existing credits.
   * @param array  $new    The array of incoming credits.
   * @param string $table  The database table to update.
   * @param string $pkey   The primary key of the object to update.
   *
   * @return null Nothing.
   */
  private function removeOldCredits($old, $new, $table, $pkey) {
    foreach ($old as $credit) {
      if (!in_array($credit, $new)) {
        self::$db->query(
          'UPDATE '.$table.' SET effective_to=NOW()'
          . 'WHERE '.$pkey.'=$1 AND creditid=$2 AND credit_type_id=$3',
          [$this->getID(), $credit['User']->getID(), $credit['type']],
          true
        );
      }
    }
  }

  /**
   * Creates any new credits that are not in the existing credits set.
   *
   * @param array  $old    The array of existing credits.
   * @param array  $new    The array of incoming credits.
   * @param string $table  The database table to update.
   * @param string $pkey   The primary key of the object to update.
   *
   * @return null Nothing.
   */
  private function addNewCredits($old, $new, $table, $pkey) {
    foreach ($new as $credit) {
      //Look for an existing credit
      if (!in_array($credit, $old)) {
        //Doesn't seem to exist.
        self::$db->query(
          'INSERT INTO '.$table.' ('.$pkey.', credit_type_id, creditid, effective_from,'
          . 'memberid, approvedid) VALUES ($1, $2, $3, NOW(), $4, $4)',
          [
            $this->getID(),
            $credit['type'],
            $credit['memberid'],
            MyRadio_User::getCurrentOrSystemUser()->getID()
          ],
          true
        );
      }
    }
  }

  /**
   * Updates the local credits cache for this object.
   *
   * @param array  $new  The array of incoming credits
   * @param array  $types  The array of incoming credit types.
   *
   * @return null Nothing.
   */
  private function updateLocalCredits($new) {
    $this->credits = $new;
  }

  protected static function getCreditName($credit_id) {
    if (empty(self::$credit_names)) {
      $r = self::$db->fetch_all('SELECT credit_type_id, name FROM people.credit_type');

      foreach ($r as $v) {
        self::$credit_names[$v['credit_type_id']] = $v['name'];
      }
    }

    return empty(self::$credit_names[$credit_id]) ? 'Contrib' : self::$credit_names[$credit_id];
  }
}
?>
