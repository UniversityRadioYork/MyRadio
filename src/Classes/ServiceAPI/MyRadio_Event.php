<?php


namespace MyRadio\ServiceAPI;


use MyRadio\MyRadio\CoreUtils;
use MyRadio\MyRadioException;
use Recurr\Rule;

class MyRadio_Event extends ServiceAPI
{
    private const BASE_SQL = "SELECT eventid, title, description_html, start_time, end_time, hostid, rrule, master_id
                                FROM public.events";

    /**
     * @var int
     */
    private $eventid;

    /**
     * @var string
     */
    private $title;

    /**
     * @var string
     */
    private $descriptionHtml;

    /**
     * @var int
     */
    private $startTime;

    /**
     * @var int
     */
    private $endTime;

    /**
     * @var int
     */
    private $hostId;

    /**
     * @var string|null
     */
    private $rrule;

    /**
     * @var int|null
     */
    private $masterId;

    public function __construct(array $data)
    {
        $this->eventid = (int)$data['eventid'];
        $this->title = $data['title'];
        $this->descriptionHtml = $data['description_html'];

        $this->startTime = strtotime($data['start_time']);
        $this->endTime = strtotime($data['end_time']);

        $this->hostId = (int)$data['hostid'];

        $this->rrule = $data['rrule'];

        $this->masterId = is_null($data['master_id']) ? null : (int)$data['master_id'];
    }

    /**
     * @return int
     */
    public function getID(): int
    {
        return $this->eventid;
    }

    /**
     * @return string
     */
    public function getTitle(): string
    {
        return $this->title;
    }

    /**
     * @return string
     */
    public function getDescriptionHtml(): string
    {
        return $this->descriptionHtml;
    }

    /**
     * @return int
     */
    public function getStartTime(): int
    {
        return $this->startTime;
    }

    /**
     * @return int
     */
    public function getEndTime(): int
    {
        return $this->endTime;
    }

    /**
     * @return int
     */
    public function getHostId(): int
    {
        return $this->hostId;
    }

    /**
     * @return string|null
     */
    public function getRrule(): ?string
    {
        return $this->rrule;
    }

    /**
     * @return int|null
     */
    public function getMasterId(): ?int
    {
        return $this->masterId;
    }

    /**
     * Get the next N events.
     *
     * @param $n int the number of events to get
     * @return MyRadio_Event[] events
     */
    public static function getNext($n=5)
    {
        $sql = 'SELECT eventid FROM public.events WHERE start_time >= NOW() ORDER BY start_time ASC LIMIT $1';
        $rows = self::$db->fetchColumn($sql, [$n]);
        return self::resultSetToObjArray($rows);
    }

    public static function create($data = [])
    {
        // Validate
        $requiredFields = ['title', 'description_html', 'start_time', 'end_time'];
        foreach ($requiredFields as $field) {
            if (!(isset($data[$field]))) {
                throw new MyRadioException("Missing $field", 400);
            }
        }

        // First, create the master
        $hostid = MyRadio_User::getCurrentOrSystemUser()->getID();

        $sql = "INSERT INTO public.events (title, description_html, start_time, end_time, hostid, rrule)
                VALUES ($1, $2, $3, $4, $5, $6) RETURNING eventid";

        $result = self::$db->fetchColumn($sql, [
            $data['title'], $data['description_html'],
            CoreUtils::getTimestamp($data['start_time']), CoreUtils::getTimestamp($data['end_time']),
            $hostid, $data['rrule']
        ]);

        if (empty($result)) {
            throw new MyRadioException("Creation of event failed!", 500);
        }

        $newEventId = $result[0];

        // Now, apply the RRule and create child events
        if (!(empty($data['rrule']))) {
            $length = (int)$data['end_time'] - $data['start_time'];
            $start = (new \DateTime("now", new \DateTimeZone('UTC')))->setTimestamp($data['start_time']);
            $rrule = new Rule($data['rrule'], $start, null, 'UTC');
            foreach ($rrule->getRDates() as $date) {
                self::$db->fetchOne('INSERT INTO public.events
                        (title, description_html, start_time, end_time, hostid, rrule, master_id)
                        VALUES ($1, $2, $3, $4, $5, $6, $7)' . [
                    $data['title'], $data['description_html'],
                    CoreUtils::getTimestamp($date->date->getTimestamp()), CoreUtils::getTimestamp($date->date->getTimestamp() + $length),
                    $hostid, $data['rrule'],
                    $newEventId
                ]);
            }
        }

        // Return the master
        return self::factory($newEventId);
    }

    protected static function factory($itemid)
    {
        $sql = self::BASE_SQL . " WHERE eventid = $1";

        $result = self::$db->fetchOne($sql, [$itemid]);
        if (empty($result)) {
            throw new MyRadioException("Event $itemid does not exist", 404);
        }

        return new self($result);
    }

    public function toDataSource($mixins = [])
    {
        return [
            'eventid' => $this->getID(),
            'title' => $this->title,
            'description_html' => $this->descriptionHtml,
            'start_time' => CoreUtils::happyTime($this->startTime),
            'end_time' => CoreUtils::happyTime($this->endTime),
            'host' => MyRadio_User::getInstance($this->hostId)->toDataSource($mixins),
            'rrule' => $this->rrule,
            'master' => (is_null($this->masterId) ? null : self::getInstance($this->masterId)->toDataSource($mixins))
        ];
    }

}