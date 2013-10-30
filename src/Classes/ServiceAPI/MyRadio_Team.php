<?php
/**
 * Provides the Team class for MyRadio
 * @package MyRadio_Core
 */

/**
 * The Team class provides information about Committee Teams.
 * 
 * @version 20130908
 * @author Lloyd Wallis <lpw@ury.org.uk>
 * @package MyRadio_Core
 * @uses \Database
 * 
 */
class MyRadio_Team extends ServiceAPI {
  /**
   * The ID of the Team
   * @var int
   */
  private $teamid;
  
  /**
   * Team name e.g. "Computing Team"
   * @var String
   */
  private $name;
  /**
   * Officer email alias e.g. "computing"
   * @var String
   */
  private $alias;
  /**
   * The weight of the Team, when listing on a page.
   * @var int
   */
  private $ordering;
  /**
   * A description of the Team.
   * @var String
   */
  private $description;
  /**
   * (c)urrent or (h)istorical.
   * @var char
   */
  private $status;
  /**
   * Officer positions in this team
   * @var int[]
   */
  private $officers;
  
  protected function __construct($id) {
    $result = self::$db->fetch_one('SELECT * FROM public.team '
            . 'WHERE teamid=$1', [$id]);
    
    if (empty($result)) {
      throw new MyRadioException('Team '.$id.' does not exist!', 404);
    } else {
      $this->teamid = (int)$id;
      $this->name = $result['team_name'];
      $this->alias = $result['local_alias'];
      $this->ordering = (int)$result['ordering'];
      $this->description = $result['descr'];
      $this->status = $result['status'];
      $this->officers = array_map(function($x){return (int)$x;},
              self::$db->fetch_column('SELECT officerid FROM officer '
                      . 'WHERE teamid=$1 ORDER BY ordering', [$id]));
    }
  }
  
  /**
   * Returns all the Teams available.
   * @return array
   */
  public static function getAllTeams($full = true) {
    return self::resultSetToObjArray(self::$db->fetch_column(
            'SELECT teamid FROM public.team'), $full);
  }
  
  /**
   * Get the ID for this Team
   * @return int
   */
  public function getID() {
    return $this->teamid;
  }
  
  /**
   * Get the Name of this Team
   * @return String
   */
  public function getName() {
    return $this->name;
  }
  
  /**
   * Gets the Team primary email alias.
   * 
   * @todo Database discrepancy - the actual lists themselves are defined
   * manually. Need to discuss what to do about this.
   * @return String
   */
  public function getAlias() {
    return $this->alias;
  }
  
  /**
   * Returns the weight of the Team when listing them.
   * @return int
   */
  public function getOrdering() {
    return $this->ordering;
  }
  
  /**
   * Get a description of the Team
   * @return String
   */
  public function getDescription() {
    return $this->description;
  }
  
  /**
   * (c)urrent or (h)istorical.
   * @return char
   */
  public function getStatus() {
    return $this->status;
  }
  
  /**
   * (o)fficer, (a)ssistant head of team, (h)ead of team
   * or (m)ember (not actually an Officer, just in team)
   * @return char
   */
  public function getType() {
    return $this->type;
  }
  
  /**
   * Return all Officer Positions in the team
   * @return MyRadio_Officer[]
   */
  public function getOfficers() {
    return MyRadio_Officer::resultSetToObjArray($this->officers);
  }
  
  /**
   * Return all Users who held positions in this Team
   * @return Array {'User':User, 'from':time, 'to':time|null,
   *   'memberofficerid': int, 'position': MyRadio_Officer}
   */
  public function getHistory() {
    $data = [];
    foreach ($this->getOfficers() as $officer) {
      $data = array_merge($data, array_map(function($x) use ($officer) {
        $x['position'] = $officer;
        return $x;
      }, $officer->getHistory()));
    }
    
    usort($data, function($a, $b) {
      return $b['from']-$a['from'];
    });
    
    return $data;
  }
  
  /**
   * Get Users currently in the Team
   * @return Array {'User':User, 'from':time,
   *   'memberofficerid': int, 'position': MyRadio_Officer}
   */
  public function getCurrentHolders() {
    $i = $this->getHistory();
    $result = array();
    
    foreach ($i as $o) {
      if (empty($o['to']) or $o['to'] >= time()) {
        unset($o['to']);
        $result[] = $o;
      }
    }
    return $result;
  }
  
  /**
   * Returns Officer positions that are the Assistant Head of Team
   * @return Officer[]
   */
  public function getAssistantHeadPositions() {
    return $this->getMembersOfType('a');
  }
  
  /**
   * Returns Officer positions that are the Head of Team
   * @return Officer[]
   */
  public function getHeadPositions() {
    return $this->getMembersOfType('h');
  }
  
  /**
   * Returns Officer positions that are an Officer member
   * @return Officer[]
   */
  public function getOfficerPositions() {
    return $this->getMembersOfType('o');
  }
  
  /**
   * Returns Officer positions that are a non-committee member
   * @return Officer[]
   */
  public function getMemberPositions() {
    return $this->getMembersOfType('m');
  }
  
  /**
   * Returns Officer positions that are the given type
   * @param char $type
   * @return Officer[]
   */
  private function getMembersOfType($type) {
    $data = [];
    foreach ($this->getCurrentHolders() as $holder) {
      if ($holder['position']->getType() == $type) {
        $data[] = $holder;
      }
    }
    
    return $data;
  }
  
  /**
   * Returns data about the Team.
   * 
   * @param bool $full If true, includes info about Officers in the Team
   * @return Array
   */
  public function toDataSource($full = false) {
    $data = [
        'teamid' => $this->getID(),
        'name' => $this->getName(),
        'alias' => $this->getAlias(),
        'ordering' => $this->getOrdering(),
        'description' => $this->getDescription(),
        'status' => $this->getStatus()
    ];
    
    if ($full) {
      $data['officers'] = CoreUtils::dataSourceParser($this->getCurrentHolders(), false);
      $data['history'] = CoreUtils::dataSourceParser($this->getHistory(), false);
    }
    
    return $data;
  }

}
