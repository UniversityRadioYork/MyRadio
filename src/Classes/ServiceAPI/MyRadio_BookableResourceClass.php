<?php

namespace MyRadio\ServiceAPI;

use MyRadio\MyRadio\CoreUtils;

class MyRadio_BookableResourceClass extends ServiceAPI {
	private const BASE_QUERY = <<<EOF
SELECT resource_class_id, name
FROM bookings.resource_classes
EOF;

	private $id;
	private $name;
	
	public function __construct($data) {
        parent::__construct();
		$this->id = (int) $data['resource_class_id'];
		$this->name = $data['name'];
	}

    /**
     * @return int
     */
    public function getID(): int
    {
        return $this->id;
    }

    /**
     * @return mixed
     */
    public function getName()
    {
        return $this->name;
    }

    public function getAllInstances() {
        return MyRadio_BookableResource::resultSetToObjArray(
            self::$db->fetchColumn(
                'SELECT resource_id FROM bookings.resources
                WHERE resource_class_id = $1',
                [$this->id]
            )
        );
    }

    public static function getAll() {
        $rows = self::$db->fetchAll(self::BASE_QUERY);
        $result = [];
        foreach ($rows as $row) {
            $result[] = new self($row);
        }

        return $result;
    }

	public function toDataSource($mixins = [])
    {
        return [
            'id' => $this->getID(),
            'name' => $this->getName()
        ];
    }
}