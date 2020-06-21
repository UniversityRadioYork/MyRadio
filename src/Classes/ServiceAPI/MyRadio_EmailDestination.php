<?php


namespace MyRadio\ServiceAPI;

use MyRadio\Config;
use MyRadio\Database;
use MyRadio\MyRadioException;

/**
 * Helper class for MyRadio_User::getAllEmails()
 * @package MyRadio\ServiceAPI
 */
class MyRadio_EmailDestination
{
    private $alias_id;
    private $source;
    private $reason;
    private $destination;

    public function __construct(array $data) {
        $this->alias_id = $data['alias_id'];
        $this->source = $data['source'];
        $this->reason = $data['reason'];
        $this->destination = $data['destination'];
    }

    /**
     * @return int|null
     */
    public function getAliasId()
    {
        return $this->alias_id;
    }

    public function getAlias()
    {
        if ($this->alias_id !== null) {
            return MyRadio_Alias::getInstance($this->alias_id);
        }
        return null;
    }

    /**
     * @return string
     */
    public function getSource()
    {
        return $this->source;
    }

    /**
     * "member", "officer", "personal", "team", "list_optin" or "list_auto"
     * @return string
     */
    public function getReason()
    {
        return $this->reason;
    }

    /**
     * @return mixed
     */
    public function getDestination()
    {
        switch ($this->reason) {
            case 'member':
            case 'personal':
                return MyRadio_User::getInstance($this->destination);
            case 'officer':
                return MyRadio_Officer::getInstance($this->destination);
            case 'team':
                return MyRadio_Team::getInstance($this->destination);
            case 'list_optin':
            case 'list_auto':
                return MyRadio_List::getInstance($this->destination);
            default:
                $r = $this->reason;
                throw new MyRadioException("Don't know how to get destination for reason $r");
        }
    }


    public static function getAllSourcesForUser(Database $database, int $memberid) {
        // Oh dear.
        $rows = $database->fetchAll(<<<SQL
SELECT alias_id, source || '@' || $2 AS source, 'member' AS reason, destination FROM mail.alias
INNER JOIN mail.alias_member USING (alias_id)
WHERE destination = $1
UNION ALL
SELECT alias_id, source || '@' || $2 AS source, 'officer' AS reason, destination FROM mail.alias
INNER JOIN mail.alias_officer USING (alias_id)
WHERE destination IN (
    SELECT officerid FROM member_officer
    WHERE memberid = $1
    AND from_date <= NOW()
    AND (till_date IS NULL OR till_date >= NOW() + '28 days'::INTERVAL)
)
UNION ALL
SELECT alias_id, source || '@' || $2 AS source, 'list_optin' AS reason, destination FROM mail.alias
INNER JOIN mail.alias_list USING (alias_id)
WHERE destination IN (
    SELECT listid FROM public.mail_subscription WHERE memberid = $1
)
UNION
SELECT NULL AS alias_id, listaddress || '@' || $2 AS source, 'list_optin' AS reason, listid AS destination
FROM mail_list
WHERE subscribable = 't'
AND $1 IN (
    SELECT memberid FROM mail_subscription WHERE listid = mail_list.listid
)
UNION
SELECT NULL as alias_id, COALESCE(listaddress || '@' || $2, listname) AS source, 'list_auto' AS reason, listid AS destination
FROM mail_list
WHERE defn IS NOT NULL
AND $1 IN (
    SELECT memberid FROM mail.eval_list_sql(
        REPLACE(
            REPLACE(
                mail_list.defn,
                '%Y',
                COALESCE(
                    (SELECT EXTRACT(year FROM start) FROM public.terms WHERE descr = 'Autumn' AND EXTRACT(year FROM start) = EXTRACT(year FROM NOW())),
                    (SELECT EXTRACT(year FROM start) FROM public.terms WHERE descr = 'Autumn' AND EXTRACT(year FROM start) = EXTRACT(year FROM NOW()) - 1)
                )::TEXT
            ),
            '%LISTID',
            mail_list.listid::text
        )
    )
)
AND $1 NOT IN (
    SELECT memberid FROM mail_subscription WHERE listid = mail_list.listid
)
AND COALESCE(listaddress, listname) NOT IN (SELECT local_alias FROM public.team)
UNION
SELECT NULL AS alias_id, officer_alias || '@' || $2 AS listname, 'officer' AS reason, officerid AS source
FROM public.officer
WHERE (status = 'c' OR status = 'h')
AND $1 IN (
    SELECT memberid FROM member_officer
    WHERE officerid = officer.officerid
    AND from_date <= NOW()
    AND (till_date IS NULL OR till_date >= NOW() + '28 days'::INTERVAL)
)
UNION
SELECT null AS alias_id, local_alias || '@' || $2 AS listname, 'team' AS reason, teamid AS source
FROM public.team
WHERE team.local_alias IS NOT NULL
AND $1 IN (
    SELECT memberid FROM member_officer
    INNER JOIN officer o on member_officer.officerid = o.officerid
    WHERE o.teamid = team.teamid
)
UNION
SELECT NULL AS alias_id, local_alias || '@' || $2 AS listname, 'personal' AS reason, $1 AS source
FROM public.member
WHERE local_alias IS NOT NULL
AND memberid = $1
UNION
SELECT NULL AS alias_id, local_name || '@' || $2 AS listname, 'personal' AS reason, $1 AS source
FROM public.member
WHERE local_name IS NOT NULL
AND memberid = $1
SQL
        // What maniac wrote this?
,
        [$memberid, Config::$email_domain]
        );
        $result = [];
        foreach ($rows as $row) {
            $result[] = new self($row);
        }
        return $result;
    }
}