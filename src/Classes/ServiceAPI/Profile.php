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
    //Return the object if it is cached
    self::$allMembers = self::$cache->get('MyURYProfile_allMembers');
    if (self::$allMembers === false) {
      self::initDB();
      self::$allMembers = 
        self::$db->fetch_all('SELECT member.memberid, sname || \'\, \' fname AS name, l_college.descr AS college, paid
        FROM member LEFT JOIN (SELECT * FROM member_year WHERE year = $1) AS member_year
        ON ( member.memberid = member_year.memberid ), l_college
        WHERE member.college = l_college.collegeid
        ORDER BY sname ASC', array(CoreUtils::getAcademicYear()));
      self::$cache->set('MyURYProfile_allMembers', self::$allMembers);
    }
    
    return self::$allMembers;
  }
  
  public static function getThisYearsMembers() {
    //Return the object if it is cached
    self::$thisYearsMembers = self::$cache->get('MyURYProfile_thisYearsMembers');
    if (self::$thisYearsMembers === false) {
      self::initDB();
      self::$thisYearsMembers = 
        self::$db->fetch_all('SELECT member.memberid, fname, sname, l_college.descr AS college, paid
        FROM member INNER JOIN (SELECT * FROM member_year WHERE year = $1) AS member_year
        ON ( member.memberid = member_year.memberid ), l_college
        WHERE member.college = l_college.collegeid
        ORDER BY sname ASC', array(CoreUtils::getAcademicYear()));
      self::$cache->set('MyURYProfile_thisYearsMembers', self::$thisYearsMembers);
    }
    
    return self::$thisYearsMembers;
  }
  
  public static function getInstance($serviceObjectId = -1) {
      throw new MyURYException('Something, Something, Something, Profile. Something, Something, Something, MyURY.');
  }
}