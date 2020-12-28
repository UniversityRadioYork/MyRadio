<?php

/**
 * This file provides the NIPSWeb_ManagedUserPlaylist class for MyRadio - Contains My Jingles and My Beds.
 */
namespace MyRadio\NIPSWeb;

use MyRadio\ServiceAPI\MyRadio_User;

/**
 * The NIPSWeb_ManagedUserPlaylist class provide My Jingles and My Beds for users.
 *
 * @uses    \Database
 */
class NIPSWeb_ManagedUserPlaylist extends NIPSWeb_ManagedPlaylist
{
    /**
     * Initiates the UserPlaylist variables.
     *
     * @param int $playlistid The folder of the user playlist to initialise, e.g. 7449/beds
     *                        Note: Only links *non-expired* items
     */
    protected function __construct($playlistid)
    {
        $this->folder = $playlistid;

        $this->name = self::getNameFromFolder($this->folder);
    }

    /**
     * Get the User Playlist Name from the Folder path.
     *
     * @param string $id Folder
     *
     * @return string the playlist name
     */
    public static function getNameFromFolder($id)
    {
        $data = explode('/', $id);
        switch ($data[sizeof($data) - 1]) {
            case 'jingles':
                return 'My Jingles';
                break;
            case 'beds':
                return 'My Beds';
                break;
            case 'links':
                return 'My Links';
                break;
            case 'sfx':
                return 'My Sound Effects';
                break;
            case 'other':
                return 'My Misc Things';
                break;
            default:
                return 'ERR_USR_PRESET_NOT_FOUND: '.$id;
                break;
        }
    }

    /**
     * Get the unique folder of the ManagedUserPlaylist.
     *
     * @return string
     */
    public function getID()
    {
        return $this->getFolder();
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
                'SELECT manageditemid FROM bapsplanner.managed_user_items
                WHERE managedplaylistid=$1 ORDER BY title',
                ['membersmusic/'.$this->folder]
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
     * Returns the managed user playlists for the given user.
     *
     *
     * @return array of Managed User Playlists for the current user.
     */
    public static function getAllManagedUserPlaylists()
    {
        $user = MyRadio_User::getInstance();
        return [
            self::getInstance($user->getID().'/beds'),
            self::getInstance($user->getID().'/jingles'),
            self::getInstance($user->getID().'/links'),
            self::getInstance($user->getID().'/sfx'),
            self::getInstance($user->getID().'/other')
        ];
    }
}
