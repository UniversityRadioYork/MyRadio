<?php
/**
 * Provides the Officer class for MyRadio
 * @package MyRadio_Core
 */

namespace MyRadio\ServiceAPI;

use \MyRadio\MyRadioException;
use \MyRadio\MyRadio\CoreUtils;

/**
 * The Officer class provides information about Committee Officerships.
 *
 * @package MyRadio_Core
 * @uses    \Database
 */
class MyRadio_Officer extends ServiceAPI
{
    /**
     * The ID of the Officer
     * @var int
     */
    private $officerid;

    /**
     * Officer title e.g. "Station Manager"
     * @var String
     */
    private $name;
    /**
     * Officer email alias e.g. "station.manager"
     * @var String
     */
    private $alias;
    /**
     * Team the Officership is a member of.
     * @var int
     */
    private $team;
    /**
     * The weight of the Officer position, when listing on a page.
     * @var int
     */
    private $ordering;
    /**
     * A description of the position.
     * @var String
     */
    private $description;
    /**
     * (c)urrent or (h)istorical.
     * @var char
     */
    private $status;
    /**
     * (o)fficer, (a)ssistant head of team, (h)ead of team
     * or (m)ember (not actually an Officer, just in team)
     *
     * @var char
     */
    private $type;
    /**
     * Users who have held this position. Cached on first request.
     * @var Array
     */
    private $history;

    protected function __construct($id)
    {
        $result = self::$db->fetchOne(
            'SELECT * FROM public.officer '
            . 'WHERE officerid=$1',
            [$id]
        );

        if (empty($result)) {
            throw new MyRadioException('Officer '.$id.' does not exist!', 404);
        } else {
            $this->officerid = (int) $id;
            $this->name = $result['officer_name'];
            $this->alias = $result['officer_alias'];
            $this->team = (int) $result['teamid'];
            $this->ordering = (int) $result['ordering'];
            $this->description = $result['descr'];
            $this->status = $result['status'];
            $this->type = $result['type'];
        }
    }

    /**
     * Create a new Officer position
     *
     * @param  String       $name     The position name, e.g. "Station Cat"
     * @param  String       $descr    A description of the position "official feline"
     * @param  String       $alias    Email alias (may be NULL) e.g. station.cat
     * @param  int          $ordering Weighting when appearing in lists e.g. 0
     * @param  MyRadio_Team $team     The Team the Officer is part of
     * @param  char         $type     'm'ember, 'o'fficer, 'a'ssistant head, 'h'ead
     * @return MyRadio_Officer        The new Officer position
     */
    public static function createOfficer($name, $descr, $alias, $ordering, MyRadio_Team $team, $type = 'o')
    {
        return self::getInstance(
            self::$db->fetchColumn(
                'INSERT INTO public.officer
                (officer_name, officer_alias, teamid, ordering, descr, type)
                VALUES ($1, $2, $3, $4, $5, $6) RETURNING officerid',
                [$name, $alias, $team->getID(), $ordering, $descr, $type]
            )[0]
        );
    }

    /**
     * Returns all the Officers available.
     * @return array
     */
    public static function getAllOfficerPositions()
    {
        return self::resultSetToObjArray(
            self::$db->fetchColumn(
                'SELECT officerid FROM public.officer'
            )
        );
    }

    public static function standDown($memberofficerid)
    {
        self::$db->query(
            'UPDATE public.member_officer
            SET till_date = NOW()
            WHERE member_officerid = $1',
            [$memberofficerid]
        );
        // TODO update cache object & clear session automatically
    }

    /**
     * Get the ID fo this Officer
     * @return int
     */
    public function getID()
    {
        return $this->officerid;
    }

    /**
     * Get the Name of this Officer Position
     * @return String
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Gets the Officer primary email alias.
     * @return String
     */
    public function getAlias()
    {
        return $this->alias;
    }

    /**
     * Returns the Team this Officership is part of
     * @return MyRadio_Team
     */
    public function getTeam()
    {
        return MyRadio_Team::getInstance($this->team);
    }

    /**
     * Returns the weight of the Officer when listing them.
     * @return int
     */
    public function getOrdering()
    {
        return $this->ordering;
    }

    /**
     *
     * @return String
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * (c)urrent or (h)istorical.
     * @return char
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * (o)fficer, (a)ssistant head of team, (h)ead of team
     * or (m)ember (not actually an Officer, just in team)
     * @return char
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * Return all Users who held this Officership
     * @return Array {'User':User, 'from':time, 'to':time|null,
     *               'memberofficerid': int}
     */
    public function getHistory()
    {
        if (empty($this->history)) {
            $result = self::$db->fetchAll(
                'SELECT member_officerid, memberid, '
                . 'from_date, till_date FROM public.member_officer '
                . 'WHERE officerid=$1 ORDER BY from_date DESC',
                [$this->getID()]
            );

            $this->history = array_map(
                function ($x) {
                    return [
                        'User'=>$x['memberid'],
                        'from'=>strtotime($x['from_date']),
                        'to'=> empty($x['till_date']) ? null
                            : strtotime($x['till_date']),
                        'memberofficerid' => (int) $x['member_officerid']
                    ];
                },
                $result
            );
            $this->updateCacheObject();
        }

        return array_map(
            function ($x) {
                $x['User'] = MyRadio_User::getInstance($x['User']);

                return $x;
            }, $this->history
        );
    }

    /**
     * Get Users currently in the position
     * @return MyRadio_User[]
     */
    public function getCurrentHolders()
    {
        $i = $this->getHistory();
        $result = [];
        foreach ($i as $o) {
            if ($o['to'] === null) {
                $result[] = $o['User'];
            }
        }

        return $result;
    }

    /**
     * Returns data about the Officer.
     *
     * @todo   User who holds or has held position
     * @param  bool $full If true, includes info about User who holds position.
     * @return Array
     */
    public function toDataSource($full = false)
    {
        $data = [
            'officerid' => $this->getID(),
            'name' => $this->getName(),
            'alias' => $this->getAlias(),
            'team' => CoreUtils::dataSourceParser($this->getTeam(), false),
            'ordering' => $this->getOrdering(),
            'description' => $this->getDescription(),
            'status' => $this->getStatus(),
            'type' => $this->getType()
        ];

        if ($full) {
            $data['current'] = CoreUtils::dataSourceParser($this->getCurrentHolders(), false);
            $data['history'] = CoreUtils::dataSourceParser($this->getHistory(), false);
        }

        return $data;
    }
}
