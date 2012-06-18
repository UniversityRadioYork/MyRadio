<?php
/**
 * Abstractor for the Profile Module
 *
 * @author aj
 */
class Profile extends ServiceAPI {
  private static $allMembers = null;
  private static $thisYearsMembers = null;
  
  public static function getAllMembers() {
      // @todo: set cache
    self::initDB();
    if (self::$allMembers === null) {
      self::$allMembers = 
        self::$db->fetch_all('SELECT member.memberid, fname || \' \' || sname AS name, l_college.descr AS college, paid
        FROM member LEFT JOIN (SELECT * FROM member_year WHERE year = $1) AS member_year
        ON ( member.memberid = member_year.memberid ), l_college
        WHERE member.college = l_college.collegeid
        ORDER BY sname ASC', array(CoreUtils::getAcademicYear()));
    }
    
    return self::$allMembers;
  }
  
  public static function getThisYearsMembers() {
      // @todo: set cache
    self::initDB();
    if (self::$thisYearsMembers === null) {
      self::$thisYearsMembers = 
        self::$db->fetch_all('SELECT member.memberid, fname || \' \' || sname AS name, l_college.descr AS college, paid
        FROM member INNER JOIN (SELECT * FROM member_year WHERE year = $1) AS member_year
        ON ( member.memberid = member_year.memberid ), l_college
        WHERE member.college = l_college.collegeid
        ORDER BY sname ASC', array(CoreUtils::getAcademicYear()));
    }
    
    return self::$thisYearsMembers;
  }
  
  public static function getInstance($serviceObjectId = -1) {
      throw new MyURYException('Something, Something, Something, Profile. Something, Something, Something, MyURY.');
  }
}