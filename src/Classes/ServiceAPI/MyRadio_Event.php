<?php


namespace MyRadio\ServiceAPI;

use MyRadio\MyRadio\AuthUtils;
use MyRadio\MyRadio\CoreUtils;
use MyRadio\MyRadio\MyRadioForm;
use MyRadio\MyRadio\MyRadioFormField;
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
     * @param string $title
     */
    public function setTitle(string $title): void
    {
        self::$db->query('UPDATE public.events
            SET title = $2
            WHERE eventid = $1', [
                $this->getID(),
            $title
        ]);
        $this->title = $title;
        $this->updateCacheObject();
    }

    /**
     * @param string $descriptionHtml
     */
    public function setDescriptionHtml(string $descriptionHtml): void
    {
        self::$db->query('UPDATE public.events
            SET description_html = $2
            WHERE eventid = $1', [
            $this->getID(),
            $descriptionHtml
        ]);
        $this->descriptionHtml = $descriptionHtml;
        $this->updateCacheObject();
    }

    /**
     * @param int $startTime
     */
    public function setStartTime(int $startTime): void
    {
        self::$db->query('UPDATE public.events
            SET start_time = $2
            WHERE eventid = $1', [
            $this->getID(),
            CoreUtils::getTimestamp($startTime)
        ]);
        $this->startTime = $startTime;
        $this->updateCacheObject();
    }

    /**
     * @param int $endTime
     */
    public function setEndTime(int $endTime): void
    {
        self::$db->query('UPDATE public.events
            SET end_time = $2
            WHERE eventid = $1', [
            $this->getID(),
            CoreUtils::getTimestamp($endTime)
        ]);
        $this->endTime = $endTime;
        $this->updateCacheObject();
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
     * @return MyRadio_User
     */
    public function getHost(): MyRadio_User
    {
        return MyRadio_User::getInstance($this->hostId);
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
     * Updates this event's data.
     *
     * Note that this method does not do any authorisation of its own.
     *
     * @param array $data same shape as {@link MyRadio_Event::create}
     */
    public function update(array $data)
    {
        $requiredFields = ['title', 'description_html', 'start_time', 'end_time'];
        foreach ($requiredFields as $field) {
            if (!(isset($data[$field]))) {
                throw new MyRadioException("Missing $field", 400);
            }
        }

        $intFields = ['start_time', 'end_time'];
        foreach ($intFields as $intField) {
            if (!is_int($data[$intField])) {
                throw new MyRadioException("Expected $intField to be an integer", 400);
            }
        }

        self::$db->query(
            'UPDATE public.events
            SET title = $2,
            description_html = $3,
            start_time = $4,
            end_time = $5
            WHERE eventid = $1',
            [
                $this->getID(),
                $data['title'],
                $data['description_html'],
                CoreUtils::getTimestamp(['start_time']),
                CoreUtils::getTimestamp($data['end_time'])
            ]
        );

        $this->title = $data['title'];
        $this->descriptionHtml = $data['description_html'];
        $this->startTime = $data['start_time'];
        $this->endTime = $data['end_time'];

        $this->updateCacheObject();
    }

    /**
     * Deletes this event. MAKE SURE TO CHECK AUTHORISATION BEFOREHAND!
     */
    public function delete()
    {
        self::$db->query(
            'DELETE FROM public.events WHERE eventid = $1',
            [$this->getID()]
        );
        self::$cache->purge();
    }

    /**
     * Get the next N events.
     *
     * @param $n int the number of events to get
     * @return MyRadio_Event[] events
     */
    public static function getNext($n = 5)
    {
        $sql = 'SELECT eventid FROM public.events WHERE start_time >= NOW() ORDER BY start_time ASC LIMIT $1';
        $rows = self::$db->fetchColumn($sql, [$n]);
        return self::resultSetToObjArray($rows);
    }

    public static function getInRange($start, $end)
    {
        $sql = 'SELECT eventid FROM public.events WHERE start_time >= $1 AND end_time <= $2 ORDER BY start_time ASC';
        $rows = self::$db->fetchColumn(
            $sql,
            [
                CoreUtils::getTimestamp(strtotime($start)),
                CoreUtils::getTimestamp(strtotime($end))
            ]
        );
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

        $intFields = ['start_time', 'end_time'];
        foreach ($intFields as $intField) {
            if (!is_int($data[$intField])) {
                throw new MyRadioException("Expected $intField to be an integer", 400);
            }
        }

        // First, create the master
        $hostid = MyRadio_User::getCurrentOrSystemUser()->getID();

        $sql = "INSERT INTO public.events (title, description_html, start_time, end_time, hostid, rrule)
                VALUES ($1, $2, $3, $4, $5, $6) RETURNING eventid";

        self::$db->query('BEGIN');

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
                        VALUES ($1, $2, $3, $4, $5, $6, $7)', [
                    $data['title'], $data['description_html'],
                    CoreUtils::getTimestamp($date->date->getTimestamp()),
                    CoreUtils::getTimestamp($date->date->getTimestamp() + $length),
                    $hostid, $data['rrule'],
                    $newEventId
                ]);
            }
        }

        self::$db->query('COMMIT');

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

    public static function getForm()
    {
        return (
            new MyRadioForm(
                'event',
                'Events',
                'editEvent',
                [
                    'title' => 'Events',
                    'subtitle' => 'Create Event'
                ]
            )
        )->addField(new MyRadioFormField(
            'title',
            MyRadioFormField::TYPE_TEXT,
            [
                'label' => 'Event Title',
                'explanation' => 'Give this event a name.'
            ]
        ))->addField(
                new MyRadioFormField(
                    'description_html',
                    MyRadioFormField::TYPE_BLOCKTEXT,
                    [
                        'explanation' => 'Describe your event as best you can.',
                        'label' => 'Description',
                    ]
                )
            )->addField(
                new MyRadioFormField(
                    'start_time',
                    MyRadioFormField::TYPE_DATETIME,
                    [
                        'required' => true,
                        'label' => 'Start Time',
                    ]
                )
            )
            ->addField(
                new MyRadioFormField(
                    'end_time',
                    MyRadioFormField::TYPE_DATETIME,
                    [
                        'required' => false,
                        'label' => 'End Time',
                    ]
                )
            );
    }

    public function getEditForm()
    {
        return self::getForm()
            ->setSubtitle('Edit Event')
            ->editMode(
                $this->getID(),
                [
                    'title' => $this->getTitle(),
                    'description_html' => $this->getDescriptionHtml(),
                    'start_time' => strftime('%d/%m/%Y %H:%M', $this->getStartTime()),
                    'end_time' => strftime('%d/%m/%Y %H:%M', $this->getEndTime())
                ]
            );
    }

    public function toDataSource($mixins = [])
    {
        return [
            'id' => $this->getID(),
            'title' => $this->title,
            'description_html' => $this->descriptionHtml,
            'start' => CoreUtils::getIso8601Timestamp($this->startTime),
            'end' => CoreUtils::getIso8601Timestamp($this->endTime),
            'host' => MyRadio_User::getInstance($this->hostId)->toDataSource($mixins),
            'rrule' => $this->rrule,
            'master' => (is_null($this->masterId) ? null : self::getInstance($this->masterId)->toDataSource($mixins))
        ];
    }

    public function canWeEdit()
    {
        return $this->getHost()->getID() === MyRadio_User::getCurrentOrSystemUser()->getID()
            || AuthUtils::hasPermission(AUTH_EDITANYEVENT);
    }

    public function checkEditPermissions()
    {
        if ($this->getHost()->getID() !== MyRadio_User::getCurrentOrSystemUser()->getID()) {
            AuthUtils::requirePermission(AUTH_EDITANYEVENT);
        }
    }
}
