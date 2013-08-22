<?php
/**
 * Provides the Text Alias class for MyURY
 * @package MyURY_Mail
 */

/**
 * The Metadata_Common class is used to provide common resources for
 * URY assets that utilise the Metadata system.
 * 
 * The metadata system is a used to attach common attributes to an item,
 * such as a title or description. It includes versioning in the form of
 * effective_from and effective_to field, storing a history of previous values.
 * 
 * @version 20130822
 * @author Lloyd Wallis <lpw@ury.org.uk>
 * @package MyURY_Mail
 * @uses \Database
 * 
 */
class MyURY_TextAlias extends MyURY_Alias {

  protected function __construct($aliasid) {
    $result = self::$db->fetch_one('SELECT * FROM public.mail_alias_text'
            . ' WHERE aliasid=$1', [$aliasid]);
    
    if (empty($result)) {
      throw new MyURYException('Alias '.$aliasid.' does not exist!', 404);
    }
    
    $this->aliasid = (int)$aliasid;
    $this->name = $result['name'];
    $this->target = $result['dest'];
    
  }
  
  /**
   * Returns all the TextAliases available.
   * @return MyURY_TextAlias[]
   */
  public static function getAllAliases() {
    return self::resultSetToObjArray(self::$db->fetch_column(
            'SELECT aliasid FROM public.mail_alias_text'));
  }

}
