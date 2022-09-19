<?php


namespace MyRadio\ServiceAPI;

use DateTime;
use MyRadio\MyRadio\AuthUtils;
use MyRadio\MyRadio\CoreUtils;
use MyRadio\MyRadio\MyRadioForm;
use MyRadio\MyRadio\MyRadioFormField;
use MyRadio\MyRadioException;
use Recurr\Rule;
use Spatie\IcalendarGenerator\Components\Event;

class MyRadio_Event extends ServiceAPI
{
    private const BASE_SQL = "SELECT eventid, title, description_html, start_time, end_time, hostid
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

    public function __construct(array $data)
    {
        $this->eventid = (int)$data['eventid'];
        $this->title = $data['title'];
        $this->descriptionHtml = $data['description_html'];

        $this->startTime = strtotime($data['start_time']);
        $this->endTime = strtotime($data['end_time']);

        $this->hostId = (int)$data['hostid'];
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
                CoreUtils::getTimestamp($data['start_time']),
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

        $hostid = MyRadio_User::getCurrentOrSystemUser()->getID();

        $sql = "INSERT INTO public.events (title, description_html, start_time, end_time, hostid)
                VALUES ($1, $2, $3, $4, $5) RETURNING eventid";

        $result = self::$db->fetchColumn($sql, [
            $data['title'], $data['description_html'],
            CoreUtils::getTimestamp($data['start_time']), CoreUtils::getTimestamp($data['end_time']),
            $hostid
        ]);

        return self::factory($result[0]);
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
        )->addField(
            new MyRadioFormField(
                'title',
                MyRadioFormField::TYPE_TEXT,
                [
                    'label' => 'Event Title',
                    'explanation' => 'Give this event a name.'
                ]
            )
        )->addField(
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
        ];
    }

    public function toIcalEvent()
    {
        return Event::create($this->getTitle())
            ->startsAt((new DateTime())->setTimestamp($this->getStartTime()))
            ->endsAt((new DateTime())->setTimestamp($this->getEndTime()))
            ->description(html_entity_decode(strip_tags($this->getDescriptionHtml())))
            ->organizer($this->getHost()->getPublicEmail(), $this->getHost()->getName());
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

    public static function createCalendarTokenFor($memberid = null)
    {
        if ($memberid === null) {
            $currentUser = MyRadio_User::getCurrentUser();
            if ($currentUser === null) {
                throw new MyRadioException('Can\'t create a calendar token with no user!');
            }
            $memberid = $currentUser->getId();
        }
        // We could in theory have a collision, but it'll get caught by postgres.
        // The likelihood is so small that we'll just let it crash and let the user try again.
        $tokenStr = CoreUtils::randomString(16);
        self::$db->query(
            'INSERT INTO public.calendar_tokens (memberid, token_str) VALUES ($1, $2)',
            [$memberid, $tokenStr]
        );
        return $tokenStr;
    }

    public static function validateCalendarToken($token)
    {
        $result = self::$db->fetchOne(
            'SELECT memberid FROM public.calendar_tokens WHERE token_str = $1 AND revoked = FALSE',
            [$token]
        );
        if (empty($result)) {
            return null;
        }
        return $result['memberid'];
    }

    public static function getCalendarTokenFor($memberid = null)
    {
        if ($memberid === null) {
            $currentUser = MyRadio_User::getCurrentUser();
            if ($currentUser === null) {
                throw new MyRadioException('Can\'t revoke a calendar token with no user!');
            }
            $memberid = $currentUser->getId();
        }
        $result = self::$db->fetchOne(
            'SELECT token_str FROM public.calendar_tokens WHERE memberid = $1 AND revoked = FALSE',
            [$memberid]
        );
        if (empty($result)) {
            return null;
        }
        return $result['token_str'];
    }

    public static function revokeCalendarTokenFor($memberid = null)
    {
        if ($memberid === null) {
            $currentUser = MyRadio_User::getCurrentUser();
            if ($currentUser === null) {
                throw new MyRadioException('Can\'t revoke a calendar token with no user!');
            }
            $memberid = $currentUser->getId();
        }
        self::$db->query(
            'UPDATE public.calendar_tokens SET revoked = TRUE WHERE memberid = $1',
            [$memberid]
        );
    }
}
