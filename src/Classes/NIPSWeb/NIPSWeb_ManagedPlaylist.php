<?php

/**
 * This file provides the NIPSWeb_ManagedPlaylist class for MyRadio - Contains Jingles etc.
 */
namespace MyRadio\NIPSWeb;

use MyRadio\MyRadioException;
use MyRadio\ServiceAPI\MyRadio_User;

/**
 * The NIPSWeb_ManagedPlaylist class helps provide control and access to managed playlists.
 *
 * @uses    \Database
 */
class NIPSWeb_ManagedPlaylist extends \MyRadio\ServiceAPI\ServiceAPI
{
    /**
     * The Singleton store for ManagedPlaylist objects.
     *
     * @var NIPSWeb_ManagedPlaylist
     */
    private static $playlists = [];
    private $managed_playlist_id;
    protected $items;
    protected $name;
    protected $folder;
    private $item_ttl;

    /**
     * Initiates the ManagedPlaylist variables.
     *
     * @param int $playlistid The ID of the managed playlist to initialise
     *                        Note: Only links *non-expired* items
     */
    protected function __construct($playlistid)
    {
        $this->managed_playlist_id = $playlistid;
        $result = self::$db->fetchOne(
            'SELECT * FROM bapsplanner.managed_playlists WHERE managedplaylistid=$1 LIMIT 1',
            [$playlistid]
        );
        if (empty($result)) {
            throw new MyRadioException('The specified NIPSWeb Managed Playlist does not seem to exist', 404);

            return;
        }

        $this->name = $result['name'];
        $this->folder = $result['folder'];
        $this->item_ttl = $result['item_ttl'];
    }

    /**
     * Return the NIPSWeb_ManagedItems that belong to this playlist.
     *
     * @return Array[NIPSWeb_ManagedItem]
     */
    public function getItems()
    {
        if (empty($this->items)) {
            $items = self::$db->fetchColumn(
                'SELECT manageditemid FROM bapsplanner.managed_items WHERE managedplaylistid=$1
                AND (expirydate IS NULL OR expirydate > NOW())
                ORDER BY title',
                [$this->managed_playlist_id]
            );

            $this->items = [];
            foreach ($items as $id) {
                /*
                 * Pass this to the ManagedItem - it's called Dependency Injection and prevents loops and looks pretty
                 * http://stackoverflow.com/questions/4903387/can-2-singleton-classes-reference-each-other
                 * http://www.phparch.com/2010/03/static-methods-vs-singletons-choose-neither/
                 */
                $this->items[] = NIPSWeb_ManagedItem::getInstance((int) $id, $this);
            }
        }

        return $this->items;
    }

    /**
     * Get the Title of the ManagedPlaylist.
     *
     * @return string
     */
    public function getTitle()
    {
        return $this->name;
    }

    /**
     * Get the unique manageditemid of the ManagedPlaylist.
     *
     * @return int
     */
    public function getID()
    {
        return $this->managed_playlist_id;
    }

    /**
     * Get the unique path of the ManagedPlaylist.
     *
     * @return string
     */
    public function getFolder()
    {
        return $this->folder;
    }

    public static function getAllManagedPlaylists($editable_only = false)
    {
        if ($editable_only && !MyRadio_User::getInstance()->hasAuth(AUTH_EDITCENTRALRES)) {
            return [];
        }
        $result = self::$db->fetchColumn('SELECT managedplaylistid FROM bapsplanner.managed_playlists ORDER BY name');
        $response = [];
        foreach ($result as $id) {
            $response[] = self::getInstance($id);
        }

        return $response;
    }

    /**
     * Returns an array of key information, useful for Twig rendering and JSON requests.
     * @param array $mixins Mixins. Currently unused
     * @return array
     * @todo Expand the information this returns
     */
    public function toDataSource($mixins = [])
    {
        return [
            'title' => $this->getTitle(),
            'managedid' => $this->getID(),
            'folder' => $this->getFolder(),
        ];
    }
}
