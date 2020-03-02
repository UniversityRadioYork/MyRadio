<?php

namespace MyRadio\ServiceAPI;

use MyRadio\MyRadioException;

class MyRadio_BookableResource extends ServiceAPI {
	private const BASE_QUERY = <<<EOF
SELECT resources.resource_id, resources.name, resources.resource_class_id
FROM bookings.resources
EOF;

	private $id;
	private $name;
	private $classId;
	
	public function __construct($data) {
        parent::__construct();
		$this->id = (int) $data['resource_id'];
		$this->name = $data['name'];
		$this->classId = $data['resource_class_id'];
	}

	public function getID() {
		return $this->id;
	}

	public function getName() {
		return $this->name;
	}

	public function getClass() {
		return MyRadio_BookableResourceClass::getInstance($this->classId);
	}

	public static function getAll() {
	    $rows = self::$db->fetchAll(self::BASE_QUERY);
	    $result = [];
	    foreach ($rows as $row) {
	        $result[] = new self($row);
        }

	    return $result;
    }

	public function getAllBookings() {
		return MyRadio_ResourceBooking::resultSetToObjArray(
			self::$db->fetchColumn(
				'SELECT booking_id FROM bookings.booking_resources
				WHERE resource_id = $1',
				[$this->id]
			)
		);
	}

    protected static function factory($itemid)
    {
        $result = self::$db->fetchOne(self::BASE_QUERY . 'WHERE resource_id = $1', [$itemid]);

        if (empty($result)) {
            throw new MyRadioException('That resource does not exist.', 404);
        }

        return new self($result);
    }


    public function toDataSource($mixins = [])
    {
        return [
            'id' => $this->getID(),
            'name' => $this->getName(),
            'class' => $this->getClass()->toDataSource($mixins)
        ];
    }
}