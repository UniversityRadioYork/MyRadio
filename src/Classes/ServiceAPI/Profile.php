<?php
/**
 * Abstractor for the Profile Module
 *
 * @author aj
 */
class Profile extends ServiceAPI {
  private static $allMembers = null;
  
  public static function getAllMembers() {
      // @todo: set cache
    self::initDB();
    if (self::$allMembers === null) {
      self::$allMembers = 
        self::$db->fetch_all('SELECT member.memberid, fname || \' \' || sname AS name, college, paid
        FROM member LEFT JOIN (SELECT * FROM member_year WHERE year = $1) AS member_year
        ON ( member.memberid = member_year.memberid )
        ORDER BY sname ASC', array(CoreUtils::getAcademicYear()));
    }
    
    return self::$allMembers;
  }
  
  public static function getThisYearsMembers() {
      // @todo: set cache
    self::initDB();
    if (self::$allMembers === null) {
      self::$allMembers = 
        self::$db->fetch_all('SELECT member.memberid, fname || \' \' || sname AS name, college, paid
        FROM member INNER JOIN (SELECT * FROM member_year WHERE year = $1) AS member_year
        ON ( member.memberid = member_year.memberid )
        ORDER BY sname ASC', array(CoreUtils::getAcademicYear()));
    }
    
    return self::$allMembers;
  }
  
  public static function getInstance($serviceObjectId = -1) {
      throw new MyURYException('Something, Something, Something, Profile. Something, Something, Something, MyURY.');
  }
}