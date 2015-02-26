<?php
/**
 * Provides the Officer class for MyRadio
 * @package MyRadio_Core
 */

namespace MyRadio\ServiceAPI;

use \MyRadio\MyRadioException;
use \MyRadio\MyRadio\CoreUtils;
use \MyRadio\MyRadio\MyRadioForm;
use \MyRadio\MyRadio\MyRadioFormField;

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
    /**
     * Stores the Officer's permissions
     * @var Array
     */
    private $permissions;


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

            //Get the officer's permissions
            $this->permissions = self::$db->fetchAll(
                'SELECT typeid AS value, descr AS text FROM public.l_action, public.auth_officer
                WHERE typeid = lookupid
                AND officerid=$1
                ORDER BY descr ASC',
                [$id]
            );
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

    /**
     * Assigns an officership to the given member
     * @param  int $memberid ID of the member for the officership
     * @api   POST
     */
    public function assignOfficer($memberid)
    {
        self::$db->query(
            'INSERT INTO public.member_officer
            (officerid, memberid, from_date)
            VALUES ($1, $2, NOW())',
            [$this->getID(), $memberid]
        );
        MyRadio_User::getInstance($memberid)->updateCacheObject();
    }

    /**
     * Stands Down the officership provided.
     *
     * @param int $memberofficerid The ID of the officership to stand down
     * @api   POST
     */
    public static function standDown($memberofficerid)
    {
        $return = self::$db->fetchColumn(
            'UPDATE public.member_officer
            SET till_date = NOW()
            WHERE member_officerid = $1
            RETURNING memberid',
            [$memberofficerid]
        );
        MyRadio_User::getInstance($return[0])->updateCacheObject();
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
     * Sets the Name of this Officer Position
     * @param String $name the new name of the officer
     * @return MyRadio_Officer the updated officer object
     */
    public function setName($name)
    {
        if ($name !== $this->name) {
            self::$db->query(
                'UPDATE public.officer
                SET officer_name = $1
                WHERE officerid=$2',
                [$name, $this->getID()]
            );
            $this->name = $name;
            $this->updateCacheObject();
        }
        return $this;
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
     * Sets the Alias of this Officer Position
     * @param String $alias the new alias of the officer
     * @return MyRadio_Officer the updated officer object
     */
    public function setAlias($alias)
    {
        if ($alias !== $this->alias) {
            self::$db->query(
                'UPDATE public.officer
                SET officer_alias = $1
                WHERE officerid=$2',
                [$alias, $this->getID()]
            );
            $this->alias = $alias;
            $this->updateCacheObject();
        }
        return $this;
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
     * Sets the Team of this Officer Position
     * @param int $team the new team of the officer
     * @return MyRadio_Officer the updated officer object
     */
    public function setTeam($team)
    {
        if ($team !== $this->team) {
            self::$db->query(
                'UPDATE public.officer
                SET teamid = $1
                WHERE officerid=$2',
                [$team, $this->getID()]
            );
            $this->team = $team;
            $this->updateCacheObject();
        }
        return $this;
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
     * Sets the Ordering of this Officer Position
     * @param int $ordering the new ordering of the officer
     * @return MyRadio_Officer the updated officer object
     */
    public function setOrdering($ordering)
    {
        if ($ordering !== $this->ordering) {
            self::$db->query(
                'UPDATE public.officer
                SET ordering = $1
                WHERE officerid=$2',
                [$ordering, $this->getID()]
            );
            $this->ordering = $ordering;
            $this->updateCacheObject();
        }
        return $this;
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
     * Sets the Description of this Officer Position
     * @param String $description the new description of the officer
     * @return MyRadio_Officer the updated officer object
     */
    public function setDescription($description)
    {
        if ($description !== $this->description) {
            self::$db->query(
                'UPDATE public.officer
                SET description = $1
                WHERE officerid=$2',
                [$description, $this->getID()]
            );
            $this->description = $description;
            $this->updateCacheObject();
        }
        return $this;
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
     * Sets the Status of this Officer Position
     * @param char $status the new status of the officer
     * @return MyRadio_Officer the updated officer object
     */
    public function setStatus($status)
    {
        if ($status !== $this->status) {
            self::$db->query(
                'UPDATE public.officer
                SET status = $1
                WHERE officerid=$2',
                [$status, $this->getID()]
            );
            $this->status = $status;
            $this->updateCacheObject();
        }
        return $this;
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
     * Sets the Type of this Officer Position
     * @param char $type the new type of the officer
     * @return MyRadio_Officer the updated officer object
     */
    public function setType($type)
    {
        if ($type !== $this->type) {
            self::$db->query(
                'UPDATE public.officer
                SET type = $1
                WHERE officerid=$2',
                [$type, $this->getID()]
            );
            $this->type = $type;
            $this->updateCacheObject();
        }
        return $this;
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
     * Returns all the officer's active permission flags
     * @return Array
     */
    public function getPermissions()
    {
        return $this->permissions;
    }

    /**
     * Adds a permission flag to the officer
     * @param $permissionid the permission to add
     */
    public function addPermission($permissionid)
    {
        self::$db->query(
            'INSERT INTO public.auth_officer
            (officerid, lookupid)
            VALUES ($1, $2)',
            [$this->getID(), $permissionid]
        );
        $this->updateCacheObject();
        return $this;
    }

    /**
     * Removes a permission flag from the officer
     * @param int $permissionid the permission to remove
     * @api   POST
     */
    public function revokePermission($permissionid)
    {
        self::$db->query(
            'DELETE from public.auth_officer
            WHERE officerid = $1
            AND lookupid = $2',
            [$this->getID(), $permissionid]
        );
        $this->updateCacheObject();
        return $this;
    }

    /**
     * Form for Officerships
     * @return MyRadioForm
     */
    public static function getForm()
    {
        $form = (
            new MyRadioForm(
                'officerForm',
                'Profile',
                'editOfficer',
                ['title' => 'Create Officer']
            )
        )->addField(
            new MyRadioFormField(
                'name',
                MyRadioFormField::TYPE_TEXT,
                [
                    'label' => 'Title'
                ]
            )
        )->addField(
            new MyRadioFormField(
                'alias',
                MyRadioFormField::TYPE_TEXT,
                [
                    'label' => 'Email Alias'
                ]
            )
        )->addField(
            new MyRadioFormField(
                'team',
                MyRadioFormField::TYPE_SELECT,
                [
                    'label' => 'Team',
                    'options' => array_merge(
                        [
                            [
                                'value' => null,
                                'text' => 'Select a Team'
                            ]
                        ],
                        MyRadio_Team::getCurrentTeams()
                    )
                ]
            )
        )->addField(
            new MyRadioFormField(
                'ordering',
                MyRadioFormField::TYPE_NUMBER,
                [
                    'label' => 'Ordering'
                ]
            )
        )->addField(
            new MyRadioFormField(
                'description',
                MyRadioFormField::TYPE_TEXT,
                [
                    'label' => 'Description',
                    'required' => false
                ]
            )
        )->addField(
            new MyRadioFormField(
                'status',
                MyRadioFormField::TYPE_SELECT,
                [
                    'label' => 'Status',
                    'options' => array_merge(
                        [
                            [
                                'value' => null,
                                'text' => 'Select Status'
                            ]
                        ],
                        CoreUtils::getStatusLookup()
                    )
                ]
            )
        )->addField(
            new MyRadioFormField(
                'type',
                MyRadioFormField::TYPE_SELECT,
                [
                    'label' => 'Officer Type',
                    'options' => [
                        [
                            'value' => null,
                            'text' => 'Select Type'
                        ],
                        [
                            'value' => 'h',
                            'text' => 'Head of Team'
                        ],
                        [
                            'value' => 'a',
                            'text' => 'Assistant Head of Team'
                        ],
                        [
                            'value' => 'o',
                            'text' => 'Team Officer'
                        ],
                        [
                            'value' => 'm',
                            'text' => 'Team Member'
                        ]
                    ]
                ]
            )
        )->addField(
            new MyRadioFormField(
                'permissions',
                MyRadioFormField::TYPE_TABULARSET,
                [
                    'label' => 'Permissions',
                    'explanation' => 'Select permissions that you want to add.',
                    'required' => false,
                    'options' => [
                        new MyRadioFormField(
                            'permission',
                            MyRadioFormField::TYPE_SELECT,
                            [
                                'label' => 'Permission',
                                'required' => false,
                                'options' => array_merge(
                                    [
                                        [
                                            'value' => null,
                                            'text' => 'Select a Permission'
                                        ]
                                    ],
                                    CoreUtils::getAllPermissions()
                                )
                            ]
                        )
                    ]
                ]
            )
        );

        return $form;
    }

    /**
     * Edit form for an existing Officership
     * @return MyRadioForm
     */
    public function getEditForm()
    {
        return self::getForm()
            ->setTitle('Edit Officer')
            ->editMode(
                $this->getID(),
                [
                    'name' => $this->getName(),
                    'description' => $this->getDescription(),
                    'alias' => $this->getAlias(),
                    'ordering' => $this->getOrdering(),
                    'team' => $this->getTeam()->getID(),
                    'type' => $this->getType(),
                    'status' => $this->getStatus(),
                    'permissions.permission' => array_map(
                        function ($perm) {
                            return $perm['value'];
                        },
                        $this->getPermissions()
                    )
                ]
            );
    }

    /**
     * Form for assigning members to an officership
     * @return MyRadioForm
     */
    public static function getAssignForm()
    {
        $form = new MyRadioForm(
            'assignForm',
            'Profile',
            'assignOfficer',
            ['title' => 'Assign Officer']
        );
        $form->addField(
            new MyRadioFormField(
                'member',
                MyRadioFormField::TYPE_MEMBER,
                [
                    'explanation' => '',
                    'label' => 'Member'
                ]
            )
        );
        return $form;
    }

    /**
     * Returns data about the Officer.
     *
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
            'type' => $this->getType(),
            'permissions' => $this->getPermissions()
        ];

        if ($full) {
            $data['current'] = CoreUtils::dataSourceParser($this->getCurrentHolders(), false);
            $data['history'] = CoreUtils::dataSourceParser($this->getHistory(), false);
        }

        return $data;
    }

}
