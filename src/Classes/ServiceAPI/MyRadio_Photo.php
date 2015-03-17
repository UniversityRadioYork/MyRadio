<?php

/**
 * This file provides the Photo class for MyRadio
 * @package MyRadio_Core
 */

namespace MyRadio\ServiceAPI;

use \MyRadio\MyRadioException;
use \MyRadio\MyRadio\CoreUtils;

/**
 * The Photo class stores and manages information about a URY Photo
 *
 * @package MyRadio_Core
 * @uses    \Database
 */
class MyRadio_Photo extends ServiceAPI
{
    /**
     * Stores the primary key for the Photo
     * @var int
     */
    private $photoid;

    /**
     * Stores the User that created this Photo
     * @var MyRadio_User
     */
    private $owner;

    /**
     * Stores when the Photo was uploaded
     * @var int
     */
    private $date_added;

    /**
     * The file extension of the photo
     * @var String
     */
    private $format;

    /**
     * Initiates the MyRadio_Photo object
     * @param int $photoid The ID of the Photo to initialise
     */
    protected function __construct($photoid)
    {
        $this->photoid = $photoid;

        $result = self::$container['database']->fetchOne(
            'SELECT * FROM myury.photos WHERE photoid=$1',
            [$photoid]
        );
        if (empty($result)) {
            throw new MyRadioException('Photo ' . $photoid . ' does not exist!');

            return null;
        }

        $this->owner = MyRadio_User::getInstance($result['owner']);
        $this->date_added = strtotime($result['date_added']);
        $this->format = $result['format'];
    }

    /**
     * Get array of information about the object.
     * @return Array
     */
    public function toDataSource()
    {
        return [
            'photoid' => $this->getID(),
            'date_added' => CoreUtils::happyTime($this->getDateAdded()),
            'format' => $this->getFormat(),
            'owner' => $this->getOwner()->getID()
        ];
    }

    /**
     * Get the time the Photo was created
     * @return int
     */
    public function getDateAdded()
    {
        return $this->date_added;
    }

    /**
     * Get the format (file extension) of the Photo.
     * @return String
     */
    public function getFormat()
    {
        return $this->format;
    }

    /**
     * Get the unique ID of this Photo
     * @return int
     */
    public function getID()
    {
        return $this->photoid;
    }

    /**
     * Get the User that owns this Photo
     * @return MyRadio_User
     */
    public function getOwner()
    {
        return $this->owner;
    }

    /**
     * Get the web URL for loading this Photo
     * @return String
     */
    public function getURL()
    {
        return self::$container['config']->public_media_uri.'/image_meta/MyRadioImageMetadata/'.$this->getID().'.'.$this->format;
    }

    /**
     * Get the file system path to the Photo
     * @return String
     */
    public function getURI()
    {
        return self::$container['config']->public_media_path.'/image_meta/MyRadioImageMetadata/'.$this->getID().'.'.$this->format;
    }

    /**
     * Add a Photo
     * @param String $tmp_file The path to the temporary file that is the image.
     * @return MyRadio_Photo
     */
    public static function create($tmp_file)
    {
        if (!file_exists($tmp_file)) {
            throw new MyRadioException('Photo path '.$tmp_file.' does not exist!', 400);
        }

        $format = explode('/', finfo_file(finfo_open(FILEINFO_MIME_TYPE), $tmp_file))[1];

        $result = self::$container['database']->fetchColumn(
            'INSERT INTO myury.photos (owner, format) VALUES ($1, $2) RETURNING photoid',
            [MyRadio_User::getInstance()->getID(), $format]
        );
        $id = $result[0];
        $photo = self::getInstance($id);
        if (!move_uploaded_file($tmp_file, $photo->getURI())) {
            self::$container['database']->query('DELETE FROM myury.photos WHERE photoid=$1', [$id]);
            throw new MyRadioException('Failed to move new Photo from '.$tmp_file.' to '.$photo->getURI().'. Are permissions for the destination right?', 500);
        }

        return $photo;
    }
}
