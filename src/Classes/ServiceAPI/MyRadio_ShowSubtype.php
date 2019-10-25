<?php

/**
 * Provides the ShowSubtype class for MyRadio
 */

namespace MyRadio\ServiceAPI;

use MyRadio\MyRadioException;

/**
 * The MyRadio_ShowSubtype class provides and stores information about a show subtype.
 *
 * @uses    \Database
 */
class MyRadio_ShowSubtype extends ServiceAPI {
    /**
     * The name of this subtype. Publicly visible on the website.
     * @var string
     */
    private $name;

    /**
     * The colour of this subtype, as a hex colour code without the leading # (e.g. "ff1200")
     * @var string
     */
    private $colour;

    public function __construct($data)
    {
        parent::__construct();
        $this->name = $data['name'];
        $this->colour = $data['colour'];
    }


    /**
     * Get the name of this subtype.
     * @return string
     */
    public function getName() {
        return $this->name;
    }

    /**
     * Get the colour of this subtype, as a hex colour code without the leading #.
     * @return string
     */
    public function getColour() {
        return $this->colour;
    }

    public function toDataSource($mixins = [])
    {
        return [
            'name' => $this->getName(),
            'colour' => $this->getColour()
        ];
    }

    protected static function factory($itemid)
    {
        $sql = 'SELECT * FROM schedule.show_subtypes WHERE show_subtype_id = $1 LIMIT 1';
        $result = self::$db->fetchOne($sql, [$itemid]);

        if (empty($result)) {
            throw new MyRadioException('That subtype does not exist.', 404);
        }

        return new self($result);
    }
}