<?php
/**
 *
 * @author Lloyd Wallis <lpw@ury.org.uk>
 */
interface MyURY_DataSource {
  /**
   * Returns an Array representing this object that can be used by a MyURY DataTable implementation.
   * It should also include any links/buttons the object should have associated with it - the JS renderer can prettify
   * as needed.
   */
  public function toDataSource();
  public static function setToDataSource($array);
}
