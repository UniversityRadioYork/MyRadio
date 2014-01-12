<?php
/**
 * Provides the MetadataSubject trait for MyRadio
 * @package MyRadio_Core
 */

/**
 * The MyRadio_MetadataSubject trait adds metadata functionality to an object.
 *
 * The object obviously needs to have metadata tables in the database for this
 * to work.
 *
 * @version 20131016
 * @author Lloyd Wallis <lpw@ury.org.uk>
 * @author Matt Windsor <matt.windsor@ury.org.uk>
 * @package MyRadio_Core
 * @uses \Database
 */
trait MyRadio_MetadataSubject {
  protected static $metadata_keys = array();
  protected $metadata;

  public function getMeta($meta_string) {
    return isset($this->metadata[self::getMetadataKey($meta_string)]) ?
      $this->metadata[self::getMetadataKey($meta_string)] : null;
  }

  /**
   * Gets the id for the string representation of a type of metadata
   */
  public static function getMetadataKey($string) {
    self::cacheMetadataKeys();
    if (!isset(self::$metadata_keys[$string])) {
      throw new MyRadioException('Metadata Key ' . $string . ' does not exist');
    }
    return self::$metadata_keys[$string]['id'];
  }

  /**
   * Gets whether the type of metadata is allowed to exist more than once
   */
  public static function isMetadataMultiple($id) {
    self::cacheMetadataKeys();
    foreach (self::$metadata_keys as $key) {
      if ($key['id'] == $id) {
        return $key['multiple'];
      }
    }
    throw new MyRadioException('Metadata Key ID ' . $id . ' does not exist');
  }

  /**
   * Sets a *text* metadata key to the specified value. Does not work for image metadata.
   *
   * If any value is the same as an existing one, no action will be taken.
   * If the given key has is_multiple, then the value will be added as a new, additional key.
   * If the key does not have is_multiple, then any existing values will have effective_to
   * set to the effective_from of this value, effectively replacing the existing value.
   * This will *not* unset is_multiple values that are not in the new set.
   *
   * @param String $string_key The metadata key
   * @param mixed $value The metadata value. If key is_multiple and value is an array, will create instance
   * for value in the array.
   * @param int $effective_from UTC Time the metavalue is effective from. Default now.
   * @param int $effective_to UTC Time the metadata value is effective to. Default NULL (does not expire).
   * @param String $table The metadata table, *including* the schema.
   * @param String $id_field The ID field in the metadata table.
   */
  public function setMeta($string_key, $value, $effective_from = null, $effective_to = null, $table = null, $id_field = null) {
    $key = self::getMetadataKey($string_key); //Integer meta key
    $multiple = self::isMetadataMultiple($key); //Bool whether multiple values are allowed
    if ($effective_from === null) {
      $effective_from = time();
    }

    $old = $this->normaliseMeta($this->getMeta($string_key));
    $new = $this->normaliseMeta($value);

    if (!$multiple && 1 < count($new)) {
        throw new MyRadioException(
          'Tried to set multiple values for a single-instance metadata key!'
        );
      }

    $to_expire = array_diff($old, $new);
    if (!empty($to_expire)) {
      $this->expireMulti($key, $to_expire, $effective_from, $table, $id_field);
    }

    $to_add = array_diff($new, $old);
    if (!empty($to_add)) {
      $this->addMulti(
        $key, $to_add, $effective_from, $effective_to, $table, $id_field
      );
    }

    // Update cache
    if ($multiple) {
      if (!is_array($this->metadata[$key])) {
        $this->metadata[$key] = [$this->metadata[$key]];
      }

      $this->metadata[$key] = array_merge(
        array_diff($this->metadata[$key], $to_expire),
        $to_add
      );
    } else {
      $this->metadata[$key] = $value;
    }

    return true;
  }

  protected static function cacheMetadataKeys() {
    if (empty(self::$metadata_keys)) {
      self::initDB();
      $r = self::$db->fetch_all('SELECT metadata_key_id AS id, name,'
              . ' allow_multiple AS multiple FROM metadata.metadata_key');
      foreach ($r as $key) {
        self::$metadata_keys[$key['name']]['id'] = (int) $key['id'];
        self::$metadata_keys[$key['name']]['multiple'] = ($key['multiple'] === 't');
      }
    }
  }

  /**
   * Normalises a set of metadata to be an array.
   *
   * @param mixed $in  The incoming metadata (string or array).
   *
   * @return array $in, as an array.
   */
  private function normaliseMeta($in) {
    if (empty($in)) {
      $out = [];
    } else if (is_array($in)) {
      $out = $in;
    } else {
      $out = [$in];
    }
    return $out;
  }

  /**
   * Adds multiple metadata values for a single metadata key.
   *
   * @param string $key       The key of the metadata row to add.
   * @param array  $values    The values of the metadata row to add.
   * @param int    $from      The time at which the metadata should appear.
   * @param int    $to        The time at which the metadata should expire.
   * @param string $table     The metadata table on which we're adding.
   * @param string $id_field  The field in $table storing the object ID.
   *
   * @return null Nothing.
   */
  private function addMulti($key, $values, $from, $to, $table, $id_field) {
    $sql = 'INSERT INTO ' . $table
      . ' (metadata_key_id, ' . $id_field . ', memberid, approvedid, metadata_value, effective_from, effective_to) VALUES ';
    $params = array($key, $this->getID(), MyRadio_User::getCurrentOrSystemUser()->getID(), CoreUtils::getTimestamp($time),
      $effective_to == null ? null : CoreUtils::getTimestamp($effective_to));

    $param_counter = 6;
    foreach ($values as $value) {
      $sql .= '($1, $2, $3, $3, $' . $param_counter . ', $4, $5),';
      $params[] = $value;
      $param_counter++;
    }
    //Remove the extra comma
    $sql = substr($sql, 0, -1);

    self::$db->query($sql, $params);
  }

  /**
   * Expires multiple metadata values for a single metadata key.
   *
   * @param string $key       The key of the metadata row to expire.
   * @param array  $values    The values of the metadata row to expire.
   * @param int    $time      The time at which the metadata should expire.
   * @param string $table     The metadata table on which we're expiring.
   * @param string $id_field  The field in $table storing the object ID.
   *
   * @return null Nothing.
   */
  private function expireMulti($key, $values, $time, $table, $id_field) {
    // TODO: Do this in one query?
    foreach ($values as $value) {
      $this->expire($key, $value, $time, $table, $id_field);
    }
  }

  /**
   * Expires any currently active metadata with a given key and value.
   *
   * @param string $key       The key of the metadata row to expire.
   * @param string $value     The value of the metadata row to expire.
   * @param int    $time      The time at which the metadata should expire.
   * @param string $table     The metadata table on which we're expiring.
   * @param string $id_field  The field in $table storing the object ID.
   *
   * @return null Nothing.
   */
  private function expire($key, $value, $time, $table, $id_field) {
    self::$db->query(
      'UPDATE ' . $table . '
      SET effective_to = $1
      WHERE metadata_key_id =$2
      AND ' . $id_field . ' =$3
      AND metadata_value = $4
      AND (effective_to IS NULL OR effective_to > $1);',
      [CoreUtils::getTimestamp($time), $key, $this->getID(), $value]
    );
  }
}
?>
