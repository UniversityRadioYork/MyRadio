<?php
/**
 * Provides the MyRadio_TrainingStatus class for MyRadio.
 */
namespace MyRadio\ServiceAPI;

use MyRadio\MyRadioException;

/**
 * The TrainingStatus class provides information about the URY Training States
 * and members who have achieved them.
 *
 * Historically, and databasically (that's a word now), Training Statuses have been
 * referred to as Presenter Statuses. With the increasing removal detachment from
 * just "presenter" training, and more towards any activity, "Training Status"
 * was adopted.
 */
class MyRadio_TrainingStatus extends ServiceAPI
{
    /**
     * The ID of the Training Status.
     *
     * @var int
     */
    private $presenterstatusid;

    /**
     * The Title of the Training Status.
     *
     * @var string
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
     * @var MyRadio_TrainingStatus
     */
    private $depends;

    /**
     * The Training Status a member must have in order to award this one.
     *
     * @var MyRadio_TrainingStatus
     */
    private $can_award;

    /**
     * A long description of the capabilities of a member with this Training Status.
     *
     * @var string
     */
    private $detail;

    /**
     * Users who have achieved this Training Status.
     *
     * This array is initialised the first time it is requested.
     *
     * @var int[]
     */
    private $awarded_to = null;

    /**
     * Permissions granted to Users with this Training Status.
     *
     * @var int[]
     */
    private $permissions;

    /**
     * Create a new TrainingStatus object. Generally, you should use getInstance.
     *
     * @param int $statusid The ID of the TrainingStatus.
     *
     * @throws MyRadioException
     */
    protected function __construct($statusid)
    {
        $this->presenterstatusid = (int) $statusid;

        $result = self::$db->fetchOne('SELECT * FROM public.l_presenterstatus WHERE presenterstatusid=$1', [$statusid]);

        if (empty($result)) {
            throw new MyRadioException('The specified Training Status ('.$statusid.') does not seem to exist', 404);

            return;
        }

        $this->descr = $result['descr'];
        $this->ordering = (int) $result['ordering'];
        $this->detail = $result['detail'];

        $this->depends = empty($result['depends']) ? null : $result['depends'];
        $this->can_award = empty($result['can_award']) ? null : $result['can_award'];

        
    }

    /**
     * Get the presenterstatusid.
     *
     * @return int
     */
    public function getID()
    {
        return $this->presenterstatusid;
    }

    /**
     * Get the Title.
     *
     * Internally, this is the `descr` field for compatibility.
     *
     * @return string
     */
    public function getTitle()
    {
        return $this->descr;
    }

    /**
     * Get details about the TrainingStatus' purpose.
     *
     * @return string
     */
    public function getDetail()
    {
        return $this->detail;
    }

    /**
     * Get the permissions this Training Status grants.
     *
     * @return int[]
     */
    public function getPermissions()
    {
        if (!isset($this->permissions)){
            $this->permissions = array_map(
                'intval',
                self::$db->fetchColumn(
                    'SELECT typeid FROM public.auth_trainingstatus WHERE presenterstatusid=$1',
                    [$this->presenterstatusid]
                )
            );
        }
        return $this->permissions;
    }

    /**
     * Get the TrainingStatus a member must have before being awarded this one, if any.
     *
     * Returns null if there is no dependency.
     *
     * @return MyRadio_TrainingStatus
     */
    public function getDepends()
    {
        return empty($this->depends) ? null : self::getInstance($this->depends);
    }

    /**
     * Checks if the user has the Training Status the one depends on.
     *
     * @param MyRadio_User $user Default current User.
     *
     * @return bool True if no dependency or dependency gained, false otherwise.
     */
    public function hasDependency(MyRadio_User $user = null)
    {
        if ($user === null) {
            $user = MyRadio_User::getInstance();
        }

        return $this->getDepends() == null or $this->getDepends()->isAwardedTo($user);
    }

    /**
     * Gets the TrainingStatus a member must have before awarding this one.
     *
     * @return MyRadio_TrainingStatus
     */
    public function getAwarder()
    {
        return self::getInstance($this->can_award);
    }

    /**
     * Returns if the User can Award this Training Status.
     *
     * @param MyRadio_User $user
     *
     * @return bool
     */
    public function canAward(MyRadio_User $user = null)
    {
        if ($user === null) {
            $user = MyRadio_User::getInstance();
        }

        if ($user->hasAuth(AUTH_AWARDANYTRAINING)) {
            // I am become trainer, doer of trainings
            return true;
        }

        return $this->getAwarder()->isAwardedTo($user);
    }

    /**
     * Get an array of all UserTrainingStatuses this TrainingStatus has been
     * awarded to, and hasn't been revoked from.
     *
     * @param int $ids If true, just returns User Training Status IDs instead of
     *                 UserTrainingStatuses.
     *
     * @return MyRadio_User[]|int
     */
    public function getAwardedTo($ids = false)
    {
        if ($this->awarded_to === null) {
            $this->awarded_to = self::$db->fetchColumn(
                'SELECT memberpresenterstatusid FROM member_presenterstatus
                WHERE presenterstatusid=$1 AND revokedtime IS NULL',
                [$this->getID()]
            );
        }

        return $ids ? $this->awarded_to : MyRadio_UserTrainingStatus::resultSetToObjArray($this->awarded_to);
    }

    /**
     * Checks if the User has this Training Status.
     *
     * @param MyRadio_User $user
     *
     * @return bool
     */
    public function isAwardedTo(MyRadio_User $user = null)
    {
        if ($user === null) {
            $user = MyRadio_User::getInstance();
        }

        return in_array(
            $user->getID(),
            array_map(
                function ($x) {
                    return $x->getAwardedTo()->getID();
                },
                $this->getAwardedTo()
            )
        );
    }

    /**
     * Get an array of properties for this TrainingStatus.
     * @param array $mixins Mixins. Unused.
     * @return array
     */
    public function toDataSource($mixins = [])
    {
        return [
            'status_id' => $this->getID(),
            'title' => $this->getTitle(),
            'detail' => $this->getDetail(),
            'depends' => $this->getDepends(),
            'awarded_by' => $this->getAwarder(),
        ];
    }

    /**
     * Get all Training Statuses.
     *
     * @return MyRadio_TrainingStatus[]
     */
    public static function getAll()
    {
        return self::resultSetToObjArray(
            self::$db->fetchColumn(
                'SELECT presenterstatusid FROM public.l_presenterstatus
                ORDER BY presenterstatusid'
            )
        );
    }

    /** Get all Training Statuses the user can train as options for MyRadioFormField
     * 
     * @param MyRadio_User $user The user trying to award status
     * 
     * @return array
    */

    public static function getOptionsToTrain($user){
        $options = [];
        foreach (self::getAll() as $status){
            if ($status->canAward($user)){
                $options[] = ["value" => $status->getID(), "text" => $status->getTitle()];
            }
        }
        return $options;
    }

    /**
     * The all the Training Statuses the User can currently be awarded.
     *
     * A User cannot award themselves statuses.
     *
     * @param MyRadio_User $to The User getting the Training Status.
     * @param MyRadio_User $by The User awarding the Training Status.
     *
     * @return MyRadio_TrainingStatus[]
     */
    public static function getAllAwardableTo(MyRadio_User $to, MyRadio_User $by = null)
    {
        if ($by === null) {
            $by = MyRadio_User::getInstance();
        }
        if ($to === $by) {
            return [];
        }

        $statuses = [];
        foreach (self::getAll() as $status) {
            if ((!$status->isAwardedTo($to))
                && $status->hasDependency($to)
                && $status->canAward($by)
            ) {
                $statuses[] = $status;
            }
        }

        return $statuses;
    }

    /**
     * All the Training Status a User can be awarded, regardless of who's awarding
     * 
     * @param MyRadio_User $to The User being awarded the training
     * 
     * @return MyRadio_TrainingStatus[]
     */

     public static function getAllToBeEarned(MyRadio_User $to){
        $statuses = [];
        foreach (self::getAll() as $status) {
            if ((!$status->isAwardedTo($to))
                && $status->hasDependency($to)
            ) {
                $statuses[] = $status;
            }
        }

        return $statuses;
     }
}
