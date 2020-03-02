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
        $this->id = (int)$data['resource_id'];
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
     * @param array $params An assoc array (possibly from JSON), generally in the shape of toDataSource()
     * (exception being that resources and members can be either an array of objects or of IDs)
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
        for ($i = 0; $i < count($params['resources']); $i++) {
            if ($params['resources'][$i] instanceof MyRadio_BookableResource) {
                $params['resources'][$i] = $params['resources'][$i]->getID();
            }
        }
        for ($i = 0; $i < count($params['members']); $i++) {
            if ($params['members'][$i] instanceof MyRadio_User) {
                $params['members'][$i] = $params['members'][$i]->getID();
            }
        }

        $creatorId = MyRadio_User::getCurrentOrSystemUser()->getID();

        self::initDB();

        $result = self::$db->fetchColumn(
            'INSERT INTO bookings.bookings (start_time, end_time, priority, creator)
            VALUES ($1, $2, $3, $3)',
            [
                $params['start_time'],
                $params['end_time'],
                $params['priority'],
                $creatorId
            ]
        );
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