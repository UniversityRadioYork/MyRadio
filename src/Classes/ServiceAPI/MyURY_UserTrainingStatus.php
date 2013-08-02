<?php
/**
 * Provides the MyURY_UserTrainingStatus class for MyURY
 * @package MyURY_Core
 */

/**
 * The UserTrainingStatus class links TrainingStatuses to Users.
 * 
 * This class does not bother with a singleton store - only Users should initialise it anyway.
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

class MyURY_UserTrainingStatus extends MyURY_TrainingStatus {

  /**
   * The ID of the UserPresenterStatus
   * 
   * @var int
   */
  private $memberpresenterstatusid;
  
  /**
   * The User the TrainingStatus was awarded to.
   * @var User
   */
  private $user;
  
  /**
   * The timestamp the UserTrainingStatus was Awarded
   * @var int
   */
  private $awarded_time;
  
  /**
   * The memberid of the User that granted this UserTrainingStatus
   * @var int
   */
  private $awarded_by;
  
  /**
   * The timestamp the UserTrainingStatus was Revoked (null if still active)
   * @var int
   */
  private $revoked_time;
  
  /**
   * The memberid of the User that revoked this UserTrainingStatus
   * @var int
   */
  private $revoked_by;
  
  /**
   * Create a new UserTrainingStatus object.
   * 
   * @param int $statusid The ID of the UserTrainingStatus.
   * @throws MyURYException
   */
  protected function __construct($statusid) {
    $this->memberpresenterstatusid = (int)$statusid;
    
    $result = self::$db->fetch_one('SELECT * FROM public.member_presenterstatus
      WHERE memberpresenterstatusid=$1', array($statusid));
    
    if (empty($result)) {
      throw new MyURYException('The specified UserTrainingStatus ('.$statusid.') does not seem to exist');
      return;
    }
    
    $this->user = User::getInstance($result['memberid']);
    $this->awarded_time = strtotime($result['completeddate']);
    $this->awarded_by = $result['confirmedby'];
    $this->revoked_time = strtotime($result['revokedtime']);
    $this->revoked_by = $result['revokedby'];
    
    parent::__construct($result['presenterstatusid']);
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
      throw new MyURYException('Invalid User Training Status ID! ('.$statusid.')', 400);
    }

    return new self($statusid);
  }
  
  /**
   * Get the memberpresenterstatusid
   * @return int
   */
  public function getUserTrainingStatusID() {
    return $this->memberpresenterstatusid;
  }
  
  /**
   * Get the User that Awarded this Training Status
   * @return User
   */
  public function getAwardedBy() {
    return User::getInstance($this->awarded_by);
  }
  
  /**
   * Get the time the User was Awarded this Training Status
   * @return int
   */
  public function getAwardedTime() {
    return $this->awarded_time;
  }
  
  /**
   * Get the User that Revoked this Training Status
   * @return User|null
   */
  public function getRevokedBy() {
    return empty($this->revoked_by) ? null : User::getInstance($this->revoked_by);
  }
  
  /**
   * Get the time the User had this Training Status Revoked
   * @return int
   */
  public function getRevokedTime() {
    return $this->revoked_time;
  }
  
  /**
   * Get an array of properties for this UserTrainingStatus.
   * 
   * @return Array
   */
  public function toDataSource() {
    $data = parent::toDataSource();
    $data['user_status_id'] = $this->getUserTrainingStatusID();
    $data['awarded_by'] = $this->getAwardedBy()->toDataSource();
    $data['awarded_time'] = $this->getAwardedTime();
    $data['revoked_by'] = ($this->getRevokedBy() === null ? null : $this->getRevokedBy()->toDataSource());
    $data['revoked_time'] = $this->getRevokedTime();
    return $data;
  }

}