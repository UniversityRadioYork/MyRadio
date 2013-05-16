<?php
/**
 * This file provides the Profile class for MyURY
 * @package MyURY_Profile
 */

/**
 * Abstractor for the Profile Module
 *
 * @author Andy Durant <aj@ury.org.uk>
 * @author Lloyd Wallis <lpw@ury.org.uk>
 * @package MyURY_Profile
 * @version 20130516
 * @uses \Database
 * @uses \CacheProvider
 */
class Profile extends ServiceAPI {
  /**
   * Stores an Array representation of all members from the getAllMembers function when it is first called.
   * This is also cached using a CacheProvider
   * @var Array
   */
  private static $allMembers = null;
  /**
   * Stores an Array representation of this year's members from the getThisYearsMembers function when it is first called
   * This is also cached using a CacheProvider
   * @var Array
   */
  private static $thisYearsMembers = null;
  /**
   * Stores an Array representation of the current officers from the getCurrentOfficers function when it is first called
   * This is also cached using a CacheProvider
   * @var Array
   */
  private static $currentOfficers = null;
  
  /**
   * Returns an Array representation of all URY members. On first run, this is cached locally in the class, and
   * shared in the CacheProvider until the Cache is cleared
   * 
   * @return Array A two-dimensional Array, each element in the first dimension container the following details about
   * a member, sorted by their name:
   * 
   * memberid: The user's unique memberid
   * name: The user's last and first names formatted as <code>sname, fname</code>
   * college: The name of the member's college (not the ID!)
   * paid: How much the member has paid this year
   */
  public static function getAllMembers() {
    //Return the object if it is cached
    self::$allMembers = self::$cache->get('MyURYProfile_allMembers');
    if (self::$allMembers === false) {
      self::__wakeup();
      self::$allMembers = 
        self::$db->fetch_all('SELECT member.memberid, sname || \', \' || fname AS name, l_college.descr AS college, paid
        FROM member LEFT JOIN (SELECT * FROM member_year WHERE year = $1) AS member_year
        ON ( member.memberid = member_year.memberid ), l_college
        WHERE member.college = l_college.collegeid
        ORDER BY sname ASC', array(CoreUtils::getAcademicYear()));
      self::$cache->set('MyURYProfile_allMembers', self::$allMembers);
    }
    
    return self::$allMembers;
  }
  
  /**
   * Returns an Array representation of this year's URY Members. On first run, this is cached locally in the class, and
   * shared in the CacheProvider until the Cache is cleared
   * 
   * @return Array A two-dimensional Array, each element in the first dimension container the following details about
   * a member, sorted by their name:
   * 
   * memberid: The user's unique memberid
   * name: The user's last and first names formatted as <code>sname, fname</code>
   * college: The name of the member's college (not the ID!)
   * paid: How much the member has paid this year
   */
  public static function getThisYearsMembers() {
    //Return the object if it is cached
    self::$thisYearsMembers = self::$cache->get('MyURYProfile_thisYearsMembers');
    if (self::$thisYearsMembers === false) {
      self::__wakeup();
      self::$thisYearsMembers = 
        self::$db->fetch_all('SELECT member.memberid, sname || \', \' || fname AS name, l_college.descr AS college, paid
        FROM member INNER JOIN (SELECT * FROM member_year WHERE year = $1) AS member_year
        ON ( member.memberid = member_year.memberid ), l_college
        WHERE member.college = l_college.collegeid
        ORDER BY sname ASC', array(CoreUtils::getAcademicYear()));
      self::$cache->set('MyURYProfile_thisYearsMembers', self::$thisYearsMembers);
    }
    
    return self::$thisYearsMembers;
  }

  /**
   * Returns an Array representation of the current URY Officers. On first run, this is cached locally in the class, and
   * shared in the CacheProvider until the Cache is cleared
   * 
   * @return Array A two-dimensional Array, each element in the first dimension container the following details about
   * a member, sorted by their name:
   * 
   * team: The team the officer is in
   * officership: The current position held
   * name: The user's last and first names formatted as <code>sname, fname</code>
   * memberid: The user's unique memberid
   */
  public static function getCurrentOfficers() {
    //Return the object if it is cached
    self::$currentOfficers = self::$cache->get('MyURYProfile_currentOfficers');
    if (self::$currentOfficers === false) {
      self::__wakeup();
      self::$currentOfficers = 
        self::$db->fetch_all('SELECT team.team_name AS team, officer.officer_name AS officership, sname || \', \' || fname AS name, member.memberid
        FROM member, officer, member_officer, team
        WHERE member_officer.memberid = member.memberid AND officer.officerid = member_officer.officerid AND officer.teamid = team.teamid AND member_officer.till_date IS NULL
        ORDER BY team.ordering, officer.ordering, sname');
      self::$cache->set('MyURYProfile_currentOfficers', self::$currentOfficers);
    }
    
    return self::$currentOfficers;
  }
}