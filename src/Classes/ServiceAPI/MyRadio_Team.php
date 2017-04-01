<?php
/**
 * Provides the Team class for MyRadio.
 */
namespace MyRadio\ServiceAPI;

use MyRadio\MyRadioException;
use MyRadio\MyRadio\CoreUtils;

/**
 * The Team class provides information about Committee Teams.
 *
 * @uses    \Database
 */
class MyRadio_Team extends ServiceAPI
{
    /**
     * The ID of the Team.
     *
     * @var int
     */
    private $teamid;

    /**
     * Team name e.g. "Computing Team".
     *
     * @var string
     */
    private $name;
    /**
     * Officer email alias e.g. "computing".
     *
     * @var string
     */
    private $alias;
    /**
     * The weight of the Team, when listing on a page.
     *
     * @var int
     */
    private $ordering;
    /**
     * A description of the Team.
     *
     * @var string
     */
    private $description;
    /**
     * (c)urrent or (h)istorical.
     *
     * @var char
     */
    private $status;
    /**
     * Officer positions in this team.
     *
     * @var int[]
     */
    private $officers;

    protected function __construct($id)
    {
        $result = self::$db->fetchOne(
            'SELECT * FROM public.team
            WHERE teamid=$1',
            [$id]
        );

        if (empty($result)) {
            throw new MyRadioException('Team '.$id.' does not exist!', 404);
        } else {
            $this->teamid = (int) $id;
            $this->name = $result['team_name'];
            $this->alias = $result['local_alias'];
            $this->ordering = (int) $result['ordering'];
            $this->description = $result['descr'];
            $this->status = $result['status'];
            $this->officers = array_map(
                function ($x) {
                    return (int) $x;
                },
                self::$db->fetchColumn(
                    'SELECT officerid FROM officer
                    WHERE teamid=$1 ORDER BY ordering',
                    [$id]
                )
            );
        }
    }

    /**
     * Returns all the Teams available.
     *
     * @return array
     */
    public static function getAllTeams()
    {
        return self::resultSetToObjArray(
            self::$db->fetchColumn('SELECT teamid FROM public.team')
        );
    }

    /**
     * Returns all the current Teams.
     *
     * @return array
     */
    public static function getCurrentTeams()
    {
        return self::resultSetToObjArray(
            self::$db->fetchColumn(
                'SELECT teamid FROM public.team
                WHERE status = \'c\'
                ORDER BY ordering ASC'
            )
        );
    }

    /**
     * Returns teamids and names for use in select boxes.
     *
     * @return array
     */
    public static function getTeamSelect()
    {
        return self::$db->fetchAll(
            'SELECT teamid AS value, team_name AS text FROM public.team
            WHERE status = \'c\'
            ORDER BY ordering ASC'
        );
    }

    /**
     * Get the ID for this Team.
     *
     * @return int
     */
    public function getID()
    {
        return $this->teamid;
    }

    /**
     * Get the Name of this Team.
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Gets the Team primary email alias.
     *
     * @todo   Database discrepancy - the actual lists themselves are defined
     * manually. Need to discuss what to do about this.
     *
     * @return string
     */
    public function getAlias()
    {
        return $this->alias;
    }

    /**
     * Returns the weight of the Team when listing them.
     *
     * @return int
     */
    public function getOrdering()
    {
        return $this->ordering;
    }

    /**
     * Get a description of the Team.
     *
     * @return string
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * (c)urrent or (h)istorical.
     *
     * @return char
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * (o)fficer, (a)ssistant head of team, (h)ead of team
     * or (m)ember (not actually an Officer, just in team).
     *
     * @return char
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * Return all Officer Positions in the team.
     *
     * @return MyRadio_Officer[]
     */
    public function getOfficers()
    {
        return MyRadio_Officer::resultSetToObjArray($this->officers);
    }

    /**
     * Return all Users who held positions in this Team.
     *
     * @return array {'User':User, 'from':time, 'to':time|null,
     *               'memberofficerid': int, 'position': MyRadio_Officer}
     */
    public function getHistory()
    {
        $data = [];
        foreach ($this->getOfficers() as $officer) {
            $data = array_merge(
                $data,
                array_map(
                    function ($x) use ($officer) {
                        $x['position'] = $officer;

                        return $x;
                    },
                    $officer->getHistory()
                )
            );
        }

        usort(
            $data,
            function ($a, $b) {
                return $b['from'] - $a['from'];
            }
        );

        return $data;
    }

    /**
     * Get Users currently in the Team.
     *
     * @return array {'User':User, 'from':time,
     *               'memberofficerid': int, 'position': MyRadio_Officer}
     */
    public function getCurrentHolders()
    {
        $i = $this->getHistory();
        $result = [];

        foreach ($i as $o) {
            if (empty($o['to']) or $o['to'] >= time()) {
                unset($o['to']);
                $result[] = $o;
            }
        }

        return $result;
    }

    /**
     * Returns Officer positions that are the Assistant Head of Team.
     *
     * @return Officer[]
     */
    public function getAssistantHeadPositions()
    {
        return $this->getMembersOfType('a');
    }

    /**
     * Returns Officer positions that are the Head of Team.
     *
     * @return Officer[]
     */
    public function getHeadPositions()
    {
        return $this->getMembersOfType('h');
    }

    /**
     * Returns Officer positions that are an Officer member.
     *
     * @return Officer[]
     */
    public function getOfficerPositions()
    {
        return $this->getMembersOfType('o');
    }

    /**
     * Returns Officer positions that are a non-committee member.
     *
     * @return Officer[]
     */
    public function getMemberPositions()
    {
        return $this->getMembersOfType('m');
    }

    /**
     * Returns Officer positions that are the given type.
     *
     * @param char $type
     *
     * @return MyRadio_Officer[]
     */
    private function getMembersOfType($type)
    {
        $data = [];
        foreach ($this->getCurrentHolders() as $holder) {
            if ($holder['position']->getType() == $type) {
                $data[] = $holder;
            }
        }

        return $data;
    }

    /**
     * Returns the team with the given local_alias.
     *
     * @param string $alias
     *
     * @return MyRadio_Team
     */
    public static function getByAlias($alias)
    {
        return self::getInstance(
            self::$db->fetchColumn('SELECT teamid FROM public.team WHERE local_alias=$1', [$alias])[0]
        );
    }

    /**
     * Create a new Team with the given paramaters.
     *
     * @param string $name     The name of the new Team
     * @param string $descr    A friendly description of the new Team
     * @param string $alias    /[a-z]+/ used for the mailing list name
     * @param int    $ordering The larger this number, the further down this Team
     *
     * @return MyRadio_Team The new Team
     */
    public static function createTeam($name, $descr, $alias, $ordering)
    {
        return self::getInstance(
            self::$db->fetchColumn(
                'INSERT INTO public.team (team_name, descr, local_alias, ordering)
                VALUES ($1, $2, $3, $4) RETURNING teamid',
                [$name, $descr, $alias, $ordering]
            )[0]
        );
    }

    /**
     * Returns data about the Team.
     *
     * @mixin officers Provides officers for the team.
     * @mixin history Provides historic data for the team.
     *
     * @return array
     */
    public function toDataSource($mixins = [])
    {
        $mixin_funcs = [
            'officers' => function (&$data) {
                $data['officers'] = CoreUtils::dataSourceParser($this->getCurrentHolders());
            },
            'history' => function (&$data) {
                $data['history'] = CoreUtils::dataSourceParser($this->getHistory());
            },
        ];

        $data = [
            'teamid' => $this->getID(),
            'name' => $this->getName(),
            'alias' => $this->getAlias(),
            'ordering' => $this->getOrdering(),
            'description' => $this->getDescription(),
            'status' => $this->getStatus(),
        ];

        $this->addMixins($data, $mixins, $mixin_funcs);

        return $data;
    }
}
