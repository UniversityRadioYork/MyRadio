<?php

namespace MyRadio\iTones;

use MyRadio\MyRadioException;

/**
 * Utility class to help with playlist categories.
 * @package MyRadio\iTones
 */
class iTones_PlaylistCategory extends \MyRadio\ServiceAPI\ServiceAPI
{
    private $id;
    private $name;
    private $description;

    protected function __construct($data)
    {
        $this->id = $data['id'];
        $this->name = $data['name'];
        $this->description = $data['description'];
    }

    /**
     * Get the ID of this category.
     * @return int
     */
    public function getID()
    {
        return $this->id;
    }

    /**
     * Gets the name of the category.
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Gets the description of this category. May contain HTML.
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
          'description' => $this->getDescription()
        ];
    }


    /**
     * Gets all defined playlist categories.
     * @return array
     */
    public static function getAll()
    {
        self::wakeup();
        $rows = self::$db->fetchAll('SELECT id, name, description FROM jukebox.playlist_categories');

        $vals = [];
        foreach ($rows as $row) {
            $vals[] = new self($row);
        }

        return \MyRadio\MyRadio\CoreUtils::setToDataSource($vals);
    }

    protected static function factory($id)
    {
        $sql = 'SELECT id, name, description FROM jukebox.playlist_categories WHERE id = $1 LIMIT 1';
        $result = self::$db->fetchOne($sql, [$id]);

        if (empty($result)) {
            throw new MyRadioException('That playlist category is in the twilight zone.', 404);
        }

        return new self($result);
    }
}
