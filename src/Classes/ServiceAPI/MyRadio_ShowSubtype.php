<?php

/**
 * Provides the ShowSubtype class for MyRadio
 */

namespace MyRadio\ServiceAPI;

use MyRadio\MyRadio\CoreUtils;
use MyRadio\MyRadioException;

/**
 * The MyRadio_ShowSubtype class provides and stores information about a show subtype.
 *
 * @uses    \Database
 */
class MyRadio_ShowSubtype extends ServiceAPI
{
    /**
     * The ID of this subtype
     * @var int
     */
    private $show_subtype_id;

    /**
     * The name of this subtype. Publicly visible on the website.
     * @var string
     */
    private $name;

    /**
     * The colour of this subtype, as a hex colour code without the leading # (e.g. "ff1200")
     * @var string
     */
    private $class;
    
    /**
     * The description of the subtype
     * @var string
    */
    private $description;

    public function __construct($data)
    {
        parent::__construct();
        $this->show_subtype_id = $data['show_subtype_id'];
        $this->name = $data['name'];
        $this->class = $data['class'];
        $this->description = $data['description'];
    }

    public function getID()
    {
        return $this->show_subtype_id;
    }


    /**
     * Get the name of this subtype.
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Get the CSS class of this subtype.
     * @return string
     */
    public function getClass()
    {
        return $this->class;
    }
    
    /**
     * Get the description of the subtype
     * @return string
    */
    public function getDescription()
    {
        return $this->description;
    }

    public function toDataSource($mixins = [])
    {
        return [
            'id' => $this->getID(),
            'name' => $this->getName(),
            'class' => $this->getClass(),
            'description' => $this->getDescription()
        ];
    }

    /**
     * Gets all subtypes.
     *
     * @return MyRadio_ShowSubtype[]
     */
    public static function getAll()
    {
        $sql = 'SELECT show_subtype_id, name, class, description FROM schedule.show_subtypes';
        $rows = self::$db->fetchAll($sql);

        $subtypes = [];
        foreach ($rows as $row) {
            $subtypes[] = new self($row);
        }

        return CoreUtils::setToDataSource($subtypes);
    }

    /**
     * Get all subtypes in a format suitable for a MyRadioFormField select field.
     * @return array
     */
    public static function getOptions()
    {
        return self::$db->fetchAll(
            'SELECT class AS value, name AS text FROM schedule.show_subtypes ORDER BY show_subtype_id ASC'
        );
    }

    protected static function factory($itemid)
    {
        $sql = 'SELECT show_subtype_id, name, class FROM schedule.show_subtypes WHERE show_subtype_id = $1 LIMIT 1';
        $result = self::$db->fetchOne($sql, [$itemid]);

        if (empty($result)) {
            throw new MyRadioException('That subtype does not exist.', 404);
        }

        return new self($result);
    }
}
