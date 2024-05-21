<?php
/**
 * Provides the MyRadio_UserTrainingStatus class for MyRadio.
 */
namespace MyRadio\ServiceAPI;

use MyRadio\MyRadioException;
use MyRadio\ServiceAPI\MyRadio_User;

/**
 * The UserTrainingStatus class links TrainingStatuses to Users.
 *
 * This class does not bother with a singleton store - only Users should initialise it anyway.
 *
 * Historically, and databasically (that's a word now), Training Statuses have been
 * referred to as Presenter Statuses. With the increasing removal detachment from
 * just "presenter" training, and more towards any activity, "Training Status"
 * was adopted.
 */
class MyRadio_UserTrainingStatus extends MyRadio_TrainingStatus
{
    /**
     * The ID of the UserPresenterStatus.
     *
     * @var int
     */
    private $memberpresenterstatusid;

    /**
     * The User the TrainingStatus was awarded to.
     *
     * @var int
     */
    private $user;

    /**
     * The timestamp the UserTrainingStatus was Awarded.
     *
     * @var int
     */
    private $awarded_time;

    /**
     * The memberid of the User that granted this UserTrainingStatus.
     *
     * @var int
     */
    private $awarded_by;

    /**
     * The timestamp the UserTrainingStatus was Revoked (null if still active).
     *
     * @var int
     */
    private $revoked_time;

    /**
     * The memberid of the User that revoked this UserTrainingStatus.
     *
     * @var int
     */
    private $revoked_by;

    /**
     * Create a new UserTrainingStatus object.
     *
     * @param int $statusid The ID of the UserTrainingStatus.
     *
     * @throws MyRadioException
     */
    protected function __construct($statusid)
    {
        $this->memberpresenterstatusid = (int) $statusid;

        $result = self::$db->fetchOne(
            'SELECT * FROM public.member_presenterstatus
            WHERE memberpresenterstatusid=$1',
            [$statusid]
        );

        if (empty($result)) {
            throw new MyRadioException('The specified UserTrainingStatus ('.$statusid.') does not seem to exist', 404);
        }

        $this->user = (int) $result['memberid'];
        $this->awarded_time = strtotime($result['completeddate']);
        $this->awarded_by = (int) $result['confirmedby'];
        $this->revoked_time = $result['revokedtime'] ? strtotime($result['revokedtime']) : null;
        $this->revoked_by = (int) $result['revokedby'];

        parent::__construct($result['presenterstatusid']);
    }

    /**
     * Get the memberpresenterstatusid.
     *
     * @return int
     */
    public function getUserTrainingStatusID()
    {
        return $this->memberpresenterstatusid;
    }

    /**
     * Get the User that Awarded this Training Status.
     *
     * @return MyRadio_User
     */
    public function getAwardedBy()
    {
        return MyRadio_User::getInstance($this->awarded_by);
    }

    /**
     * Get the User that was Awarded this Training Status.
     *
     * @return MyRadio_User
     */
    public function getAwardedTo($id = false)
    {
        return $id ? $this->user : MyRadio_User::getInstance($this->user);
    }

    /**
     * Get the time the User was Awarded this Training Status.
     *
     * @return int
     */
    public function getAwardedTime()
    {
        return $this->awarded_time;
    }

    /**
     * Get the User that Revoked this Training Status.
     *
     * @return MyRadio_User|null
     */
    public function getRevokedBy()
    {
        return empty($this->revoked_by) ? null : MyRadio_User::getInstance($this->revoked_by);
    }

    /**
     * Get the time the User had this Training Status Revoked.
     *
     * @return int
     */
    public function getRevokedTime()
    {
        return $this->revoked_time;
    }

    /**
     * Get an array of properties for this UserTrainingStatus.
     * @param array $mixins Mixins
     * @return array
     */
    public function toDataSource($mixins = [])
    {
        $data = parent::toDataSource();
        $data['user_status_id'] = $this->getUserTrainingStatusID();
        $data['awarded_to'] = [
            'display' => 'text',
            'url' => $this->getAwardedTo()->getURL(),
            'value' => $this->getAwardedTo()->getName(),
        ];
        $data['awarded_by'] = [
            'display' => 'text',
            'url' => $this->getAwardedBy()->getURL(),
            'value' => $this->getAwardedBy()->getName(),
        ];
        $data['awarded_time'] = $this->getAwardedTime();
        $data['revoked_by'] = ($this->getRevokedBy() === null ? null :
                $this->getRevokedBy()->toDataSource($mixins));
        $data['revoked_time'] = $this->getRevokedTime();

        return $data;
    }

    /**
     * Creates a new User - Training Status map, awarding that User the training status.
     *
     * @param MyRadio_TrainingStatus $status     The status to be awarded
     * @param MyRadio_User           $awarded_to The User to be awarded the training status
     * @param MyRadio_User           $awarded_by The User that is granting the training status
     *
     * @return \self
     *
     * @throws MyRadioException
     */
    public static function create(
        MyRadio_TrainingStatus $status,
        MyRadio_User $awarded_to,
        MyRadio_User $awarded_by = null
    ) {
        //Does the User already have this?
        foreach ($awarded_to->getAllTraining(true) as $training) {
            if ($training->getID() === $status->getID()) {
                return $training;
            }
        }

        if ($awarded_by === null) {
            $awarded_by = MyRadio_User::getInstance();
        }

        if (!$status->canAward($awarded_by)) {
            throw new MyRadioException($awarded_by.' does not have permission to award '.$status);
        }

        if (!$status->hasDependency($awarded_to)) {
            throw new MyRadioException($awarded_to.' does not have the prerequisite training to be awarded '.$status);
        }

        $id = self::$db->fetchColumn(
            'INSERT INTO public.member_presenterstatus (memberid, presenterstatusid, confirmedby)
            VALUES ($1, $2, $3) RETURNING memberpresenterstatusid',
            [
                $awarded_to->getID(),
                $status->getID(),
                $awarded_by->getID(),
            ]
        )[0];

        //Force the User to be updated on next request.
        self::$cache->delete(MyRadio_User::getCacheKey($awarded_to->getID()));

        return new self($id);
    }
}
