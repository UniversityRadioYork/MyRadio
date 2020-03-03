<?php

namespace MyRadio\ServiceAPI;

use MyRadio\MyRadio\CoreUtils;
use MyRadio\MyRadioException;

class MyRadio_ResourceBooking extends ServiceAPI
{
    private const BASE_QUERY = <<<EOF
SELECT bookings.booking_id, bookings.start_time, bookings.end_time, bookings.priority
FROM bookings.bookings
EOF;

    private $id;
    private $startTime;
    private $endTime;
    private $priority;

    public function __construct($data)
    {
        parent::__construct();
        $this->id = (int)$data['booking_id'];
        $this->startTime = strtotime($data['start_time']);
        $this->endTime = strtotime($data['end_time']);
        $this->priority = (int)$data['priority'];
    }

    /**
     * @return int
     */
    public function getId(): int
    {
        return $this->id;
    }

    /**
     * @return int
     */
    public function getStartTime()
    {
        return $this->startTime;
    }

    /**
     * @return int
     */
    public function getEndTime()
    {
        return $this->endTime;
    }

    /**
     * @return int
     */
    public function getPriority(): int
    {
        return $this->priority;
    }

    public function getResources()
    {
        return MyRadio_BookableResource::resultSetToObjArray(
            self::$db->fetchColumn(
                'SELECT resource_id FROM bookings.booking_resources
                WHERE booking_id = $1',
                [$this->id]
            )
        );
    }

    public function getMembers()
    {
        return MyRadio_User::resultSetToObjArray(
            self::$db->fetchColumn(
                'SELECT member_id FROM bookings.booking_members
                WHERE booking_id = $1',
                [$this->id]
            )
        );
    }

    /** Creates a new resource booking.
     *
     * The $params parameter should be in the same of toDataSource(),
     * (exception being that resources and members can be either an array of objects or of IDs)
     * @param array $params An assoc array (possibly from JSON), generally in the shape of toDataSource()
     */
    public static function create(
        $params = []
    )
    {
        $required = ['start_time', 'end_time', 'priority', 'resources', 'members'];
        foreach ($required as $field) {
            if (!isset($params[$field])) {
                throw new MyRadioException('You must provide ' . $field, 400);
            }
        }

        foreach (['start_time', 'end_time'] as $param) {
            if (!(is_int($params[$param]))) {
                $params[$param] = strtotime($params[$param]);
            }
        }

        for ($i = 0; $i < count($params['resources']); $i++) {
            if (!($params['resources'][$i] instanceof MyRadio_BookableResource)) {
                $params['resources'][$i] = MyRadio_BookableResource::getInstance($params['resources'][$i]);
            }
            if (!($params['resources'][$i]->isFreeBetween($params['start_time'], $params['end_time']))) {
                // TODO: handle priorities
                $name = $params['resources'][$i]->getName();
                throw new MyRadioException("$name is already booked at that time.");
            }
        }
        for ($i = 0; $i < count($params['members']); $i++) {
            if (!($params['members'][$i] instanceof MyRadio_User)) {
                $params['members'][$i] = MyRadio_User::getInstance($params['members'][$i]);
            }
        }

        $creatorId = MyRadio_User::getCurrentOrSystemUser()->getID();

        self::initDB();

        self::$db->query('BEGIN');

        $result = self::$db->fetchColumn(
            'INSERT INTO bookings.bookings (start_time, end_time, priority, creator)
            VALUES ($1, $2, $3, $4) RETURNING booking_id',
            [
                CoreUtils::getTimestamp($params['start_time']),
                CoreUtils::getTimestamp($params['end_time']),
                $params['priority'],
                $creatorId
            ]
        );
        if (empty($result)) {
            self::$db->query('ROLLBACK');
            throw new MyRadioException('Booking creation failed (code 1)');
        }
        $newBookingId = $result[0];

        foreach ($params['resources'] as $res) {
            self::$db->query(
                'INSERT INTO bookings.booking_resources (booking_id, resource_id) VALUES ($1, $2)',
                [$newBookingId, $res->getID()]
            );
        }

        foreach ($params['members'] as $member) {
            self::$db->query(
                'INSERT INTO bookings.booking_members (booking_id, member_id) VALUES ($1, $2)',
                [$newBookingId, $member->getID()]
            );
        }

        self::$db->query('COMMIT');

        $result = new self($newBookingId);
        return $result;
    }

    protected static function factory($itemid)
    {
        $result = self::$db->fetchOne(self::BASE_QUERY . ' WHERE booking_id = $1', [$itemid]);

        if (empty($result)) {
            throw new MyRadioException('That booking does not exist.', 404);
        }

        return new self($result);
    }

    public function toDataSource($mixins = [])
    {
        return [
            'id' => $this->id,
            'start_time' => CoreUtils::happyTime($this->startTime),
            'end_time' => CoreUtils::happyTime($this->endTime),
            'priority' => $this->priority,
            'resources' => CoreUtils::setToDataSource($this->getResources(), $mixins),
            'members' => CoreUtils::setToDataSource($this->getMembers(), $mixins)
        ];
    }
}