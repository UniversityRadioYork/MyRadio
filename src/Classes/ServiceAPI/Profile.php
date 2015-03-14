<?php
/**
 * This file provides the Profile class for MyRadio
 * @package MyRadio_Profile
 */

namespace MyRadio\ServiceAPI;

use \MyRadio\MyRadio\CoreUtils;

/**
 * Abstractor for the Profile Module
 *
 * @todo    Merge into User
 * @package MyRadio_Profile
 * @uses    \Database
 * @uses    \CacheProvider
 */
class Profile extends ServiceAPI
{
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
     * Stores an Array representation of the current officerships and members holding them from the getOfficers function when it is first called
     * This is also cached using a CacheProvider
     * @var Array
     */
    private static $officers = null;

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
    public static function getAllMembers()
    {
        //Return the object if it is cached
        self::$allMembers = self::$container['cache']->get('MyRadioProfile_allMembers');
        if (self::$allMembers === false) {

            self::$allMembers = self::$container['database']->fetchAll(
                'SELECT member.memberid, sname || \', \' || fname AS name, l_college.descr AS college, paid
                FROM member LEFT JOIN (SELECT * FROM member_year WHERE year = $1) AS member_year
                ON ( member.memberid = member_year.memberid ), l_college
                WHERE member.college = l_college.collegeid
                ORDER BY sname ASC',
                [CoreUtils::getAcademicYear()]
            );
            self::$container['cache']->set('MyRadioProfile_allMembers', self::$allMembers);
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
    public static function getThisYearsMembers()
    {


        return self::$container['database']->fetchAll(
            'SELECT member.memberid, sname || \', \' || fname AS name, l_college.descr AS college, paid
            FROM member INNER JOIN (SELECT * FROM member_year WHERE year = $1) AS member_year
            ON ( member.memberid = member_year.memberid ), l_college
            WHERE member.college = l_college.collegeid
            ORDER BY sname ASC',
            [CoreUtils::getAcademicYear()]
        );
    }

    /**
     * Returns an Array representation of the current URY Officers. On first run, this is cached locally in the class, and
     * shared in the CacheProvider until the Cache is cleared
     *
     * @return Array A two-dimensional Array, each element in the first dimension container the following details about
     * officer, sorted by their officer ordering:
     *
     * team: The team the officer is in
     * officership: The current position held
     * name: The user's last and first names formatted as <code>sname, fname</code>
     * memberid: The user's unique memberid
     */
    public static function getCurrentOfficers()
    {
        //Return the object if it is cached
        self::$currentOfficers = self::$container['cache']->get('MyRadioProfile_currentOfficers');
        if (self::$currentOfficers === false) {

            self::$currentOfficers = self::$container['database']->fetchAll(
                'SELECT team.team_name AS team, officer.officer_name AS officership, sname || \', \' || fname AS name, member.memberid
                FROM member, officer, member_officer, team
                WHERE member_officer.memberid = member.memberid AND officer.officerid = member_officer.officerid AND officer.teamid = team.teamid AND member_officer.till_date IS NULL
                ORDER BY team.ordering, officer.ordering, sname'
            );
            self::$container['cache']->set('MyRadioProfile_currentOfficers', self::$currentOfficers);
        }

        return self::$currentOfficers;
    }

    /**
     * Returns an Array representation of the current URY officerships and the member holding them. On first run, this is cached locally in the class, and
     * shared in the CacheProvider until the Cache is cleared
     *
     * @return Array A two-dimensional Array, each element in the first dimension container the following details about
     * a member, sorted by their name:
     *
     * team: The team the officer is in
     * officership: The current position held
     * name: The user's last and first names formatted as <code>fname sname</code> (If officership is filled else NULL)
     * memberid: The user's unique memberid (If officership is filled else NULL)
     */
    public static function getOfficers()
    {
        //Return the object if it is cached
        self::$officers = self::$container['cache']->get('MyRadioProfile_officers');
        if (self::$officers === false) {

            self::$officers = self::$container['database']->fetchAll(
                'SELECT team.team_name AS team, officer.type, officer.officer_name AS officership,
                fname || \' \' || sname AS name, member.memberid, officer.officerid
                FROM team
                LEFT JOIN officer ON team.teamid = officer.teamid AND officer.status = \'c\'
                LEFT JOIN member_officer ON officer.officerid = member_officer.officerid AND member_officer.till_date IS NULL
                LEFT JOIN member ON member_officer.memberid = member.memberid
                WHERE team.status = \'c\' AND officer.type != \'m\'
                ORDER BY team.ordering, officer.ordering, sname'
            );
            self::$container['cache']->set('MyRadioProfile_officers', self::$officers);
        }

        return self::$officers;
    }
}
