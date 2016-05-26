<?php

/**
 * This file provides the Photo class for MyRadio.
 */
namespace MyRadio\ServiceAPI;

use MyRadio\Config;
use MyRadio\MyRadioException;
use MyRadio\MyRadio\CoreUtils;

/**
 * The Photo class stores and manages information about a URY Photo.
 *
 * @uses    \Database
 */
class MyRadio_Photo extends ServiceAPI
{
    /**
     * Stores the primary key for the Photo.
     *
     * @var int
     */
    private $photoid;

    /**
     * Stores the User that created this Photo.
     *
     * @var MyRadio_User
     */
    private $owner;

    /**
     * Stores when the Photo was uploaded.
     *
     * @var int
     */
    private $date_added;

    /**
     * The file extension of the photo.
     *
     * @var string
     */
    private $format;

    /**
     * Initiates the MyRadio_Photo object.
     *
     * @param int $photoid The ID of the Photo to initialise
     */
    protected function __construct($photoid)
    {
        $this->photoid = (int) $photoid;

        $result = self::$db->fetchOne(
            'SELECT photoid, owner, format,
             EXTRACT(epoch FROM date_added) AS date_added
             FROM myury.photos
             WHERE photoid=$1',
            [$photoid]
        );
        if (empty($result)) {
            throw new MyRadioException('Photo '.$photoid.' does not exist!');
            return;
        }

        $this->owner = MyRadio_User::getInstance($result['owner']);
        $this->date_added = (int) $result['date_added'];
        $this->format = $result['format'];
    }

    /**
     * Get array of information about the object.
     *
     * @return array
     */
    public function toDataSource()
    {
        return [
            'photoid' => $this->getID(),
            'date_added' => $this->getDateAdded(),
            'format' => $this->getFormat(),
            'owner' => $this->getOwner()->getID(),
            'url' => $this->getURL(),
        ];
    }

    /**
     * Get the time the Photo was created.
     *
     * @return int
     */
    public function getDateAdded()
    {
        return $this->date_added;
    }

    /**
     * Get the format (file extension) of the Photo.
     *
     * @return string
     */
    public function getFormat()
    {
        return $this->format;
    }

    /**
     * Get the unique ID of this Photo.
     *
     * @return int
     */
    public function getID()
    {
        return $this->photoid;
    }

    /**
     * Get the User that owns this Photo.
     *
     * @return MyRadio_User
     */
    public function getOwner()
    {
        return $this->owner;
    }

    /**
     * Get the web URL for loading this Photo.
     *
     * @return string
     */
    public function getURL()
    {
        return Config::$public_media_uri.'/image_meta/MyRadioImageMetadata/'.$this->getID().'.'.$this->format;
    }

    /**
     * Get the file system path to the Photo.
     *
     * @return string
     */
    public function getURI()
    {
        return Config::$public_media_path.'/image_meta/MyRadioImageMetadata/'.$this->getID().'.'.$this->format;
    }

    /**
     * Add a Photo.
     *
     * @param string $tmp_file The path to the temporary file that is the image.
     *
     * @return MyRadio_Photo
     */
    public static function create($tmp_file)
    {
        if (!file_exists($tmp_file)) {
            throw new MyRadioException('Photo path '.$tmp_file.' does not exist!', 400);
        }

        $format = explode('/', getimagesize($tmp_file)['mime'])[1];

        $result = self::$db->fetchColumn(
            'INSERT INTO myury.photos (owner, format) VALUES ($1, $2) RETURNING photoid',
            [MyRadio_User::getInstance()->getID(), $format]
        );
        $id = $result[0];
        $photo = self::getInstance($id);
        if (!move_uploaded_file($tmp_file, $photo->getURI())) {
            self::$db->query('DELETE FROM myury.photos WHERE photoid=$1', [$id]);
            throw new MyRadioException('Failed to move new Photo from '.$tmp_file.' to '.$photo->getURI().'. Are permissions for the destination right?', 500);
        }

        return $photo;
    }
}
