<?php
/**
 * Provides the MyURY_TrainingStatus class for MyURY
 * @package MyURY_Core
 */

/**
 * The TrainingStatus class provides information about the URY Training States
 * and members who have achieved them.
 * 
 * Historically, and databasically (that's a word now), Training Statuses have been
 * referred to as Presenter Statuses. With the increasing removal detachment from
 * just "presenter" training, and more towards any activity, "Training Status"
 * was adopted.
 * 
 * @version 20130801
 * @author Lloyd Wallis <lpw@ury.org.uk>
 * @package MyURY_Core
 */

class MyURY_TrainingStatus extends ServiceAPI {

  /**
   * The singleton store for TrainingStatus objects
   * @var MyURY_TrainingStatus[]
   */
  private static $ts = array();
  
  /**
   * The ID of the Training Status
   * @var int
   */
  private $presenterstatusid;
  
  /**
   * The Title of the Training Status
   * @var String
   */
  private $descr;
  
  /**
   * The numerical weight of the Training Status.
   * 
   * The bigger the number, the further down the list it appears,
   * and the more senior the status.
   * 
   * @var int
   */
  private $ordering;
  
  /**
   * The Training Status a member must have before achieving this one.
   * 
   * @var MyURY_TrainingStatus
   */
  private $depends;
  
  /**
   * The Training Status a member must have in order to award this one.
   * 
   * @var MyURY_TrainingStatus
   */
  private $can_award;
  
  /**
   * A long description of the capabilities of a member with this Training Status.
   * 
   * @var String
   */
  private $detail;
  
  /**
   * Users who have achieved this Training Status.
   * 
   * This array is initialised the first time it is requested.
   * 
   * @var User[]
   */
  private $awarded_to = null;

  /**
   * Create a new TrainingStatus object. Generally, you should use getInstance.
   * 
   * @param int $statusid The ID of the TrainingStatus.
   * @throws MyURYException
   */
  protected function __construct($statusid) {
    $this->presenterstatusid = (int)$statusid;
    
    $result = self::$db->fetch_one('SELECT * FROM public.l_presenterstatus WHERE presenterstatusid=$1', array($statusid));
    
    if (empty($result)) {
      throw new MyURYException('The specified Training Status ('.$statusid.') does not seem to exist');
      return;
    }
    
    $this->descr = $result['descr'];
    $this->ordering = (int)$result['ordering'];
    $this->detail = $result['detail'];
    
    if (!isset(self::$ts[$statusid])) {
      self::$ts[$statusid] = $this;
    }
    
    $this->depends = empty($result['depends']) ? null : self::getInstance($result['depends']);
    $this->can_award = empty($result['can_award']) ? null : self::getInstance($result['can_award']);
  }

  /**
   * Get an Object for the given Training Status ID, initialising it if necessary.
   * 
   * @param int $statusid
   * @return MyURY_TrainingStatus
   * @throws MyURYException
   */
  public static function getInstance($statusid = -1) {
    self::wakeup();
    if (!is_numeric($statusid)) {
      throw new MyURYException('Invalid Training Status ID! ('.$statusid.')', 400);
    }

    if (!isset(self::$ts[$statusid])) {
      self::$ts[$statusid] = new self($statusid);
    }

    return self::$ts[$statusid];
  }
  
  /**
   * Get the presenterstatusid
   * @return int
   */
  public function getID() {
    return $this->presenterstatusid;
  }
  
  /**
   * Get the Title
   * 
   * Internally, this is the `descr` field for compatibility.
   * 
   * @return String
   */
  public function getTitle() {
    return $this->descr;
  }
  
  /**
   * Get details about the TrainingStatus' purpose
   * 
   * @return String
   */
  public function getDetail() {
    return $this->detail;
  }
  
  /**
   * Get the TrainingStatus a member must have before being awarded this one, if any.
   * 
   * Returns null if there is no dependency.
   * 
   * @return MyURY_TrainingStatus
   */
  public function getDepends() {
    return $this->depends;
  }
  
  /**
   * Checks if the user has the Training Status the one depends on.
   * 
   * @param User $user Default current User.
   * @return bool True if no dependency or dependency gained, false otherwise.
   */
  public function hasDependency(User $user = null) {
    if ($user === null) {
      $user = User::getInstance();
    }
    return (sizeof($this->getDepends()) === 0 or $this->isAwardedTo($user));
  }
  
  /**
   * Gets the TrainingStatus a member must have before awarding this one.
   * 
   * @return MyURY_TrainingStatus
   */
  public function getAwarder() {
    return $this->can_award;
  }
  
  /**
   * Returns if the User can Award this Training Status
   * @param User $user
   * @return bool
   */
  public function canAward(User $user = null) {
    if ($user === null) {
      $user = User::getInstance();
    }
    
    return $this->getAwarder()->isAwardedTo($user);
  }
  
  /**
   * Get an array of all UserTrainingStatuses this TrainingStatus has been
   * awarded to, and hasn't been revoked from.
   * 
   * @param int $ids If true, just returns User Training Status IDs instead of
   * UserTrainingStatuses.
   * @return User[]|int
   */
  public function getAwardedTo($ids = false) {
    if ($this->awarded_to === null) {
      $this->awarded_to = self::$db->fetch_column(
        'SELECT memberpresenterstatusid FROM member_presenterstatus
        WHERE presenterstatusid=$1 AND revokedtime IS NULL', [$this->getID()]);
    }
    var_dump(get_called_class());
    return $ids ? $this->awarded_to : 
            MyURY_UserTrainingStatus::resultSetToObjArray($this->awarded_to);
  }
  
  /**
   * Checks if the User has this Training Status
   * 
   * @param User $user
   * @return bool
   */
  public function isAwardedTo(User $user = null) {
    if ($user === null) {
      $user = User::getInstance();
    }
    
    return in_array($user->getID(), array_map(function($x){
      return $x->getAwardedTo()->getID();},
            $this->getAwardedTo()));
  }
  
  /**
   * Get an array of properties for this TrainingStatus.
   * 
   * @return Array
   */
  public function toDataSource() {
    return array(
        'status_id' => $this->getID(),
        'title' => $this->getTitle(),
        'detail' => $this->getDetail(),
        'depends' => $this->getDepends(),
        'awarded_by' => $this->getAwarder()
    );
  }
  
  /**
   * Get all Training Statuses.
   * @return MyURY_TrainingStatus[]
   */
  public static function getAll() {
    return self::resultSetToObjArray(self::$db->fetch_column(
            'SELECT presenterstatusid FROM public.l_presenterstatus '
            . 'ORDER BY presenterstatusid'));
  }
  
  /**
   * The all the Training Statuses the User can currently be awarded.
   * 
   * A User cannot award themselves statuses.
   * 
   * @param User $to The User getting the Training Status.
   * @param User $by The User awarding the Training Status.
   * @return MyURY_TrainingStatus[]
   */
  public static function getAllAwardableTo(User $to, User $by = null) {
    if ($by === null) {
      $by = User::getInstance();
    }
    if ($to === $by) {
      return [];
    }
    
    $statuses = [];
    foreach (self::getAll() as $status) {
      
      if ((!$status->isAwardedTo($to)) && $status->hasDependency($to)
              && $status->canAward($by)) {
        $statuses[] = $status;
      }
    }
    return $statuses;
  }

}