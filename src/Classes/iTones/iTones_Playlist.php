<?php

/**
 * This file provides the iTones_Playlist class for MyRadio - Contains a predefined list of Central tracks
 * @package MyRadio_iTones
 */

namespace MyRadio\iTones;

use \MyRadio\Config;
use \MyRadio\MyRadioException;
use \MyRadio\MyRadio\CoreUtils;
use \MyRadio\ServiceAPI\MyRadio_User;
use \MyRadio\ServiceAPI\MyRadio_Track;
use \MyRadio\MyRadio\MyRadioForm;
use \MyRadio\MyRadio\MyRadioFormField;

/**
 * The iTones_Playlist class helps provide control and access to managed playlists
 *
 * @version 20130712
 * @author Lloyd Wallis <lpw@ury.org.uk>
 * @package MyRadio_iTones
 * @uses \Database
 */
class iTones_Playlist extends \MyRadio\ServiceAPI\ServiceAPI
{
    private $playlistid;
    private $title;
    private $image;
    private $description;
    private $lock;
    private $locktime;
    protected $tracks = [];
    protected $revisionid;

    /**
     * Initiates the ManagedPlaylist variables
     * @param int $playlistid The ID of the managed playlist to initialise
     * Note: Only links *non-expired* items
     */
    protected function __construct($playlistid)
    {
        $this->playlistid = $playlistid;
        $result = self::$db->fetchOne('SELECT * FROM jukebox.playlists WHERE playlistid=$1 LIMIT 1', [$playlistid]);
        if (empty($result)) {
            throw new MyRadioException('The specified iTones Playlist does not seem to exist');

            return;
        }

        $this->title = $result['title'];
        $this->image = $result['image'];
        $this->description = $result['description'];
        $this->lock = empty($result['lock']) ? null : MyRadio_User::getInstance($result['lock']);
        $this->locktime = (int) $result['locktime'];

        $this->revisionid = (int) self::$db->fetchOne(
            'SELECT revisionid FROM jukebox.playlist_revisions
            WHERE playlistid=$1 ORDER BY revisionid DESC LIMIT 1',
            [$this->getID()]
        )['revisionid'];
    }

    public static function getTracksForm()
    {
        return (
            new MyRadioForm(
                'itones_playlistedit',
                'iTones',
                'editPlaylist',
                [
                    'title' => 'Edit Campus Jukebox Playlist'
                ]
            )
        )->addField(
            new MyRadioFormField(
                'tracks',
                MyRadioFormField::TYPE_TABULARSET,
                [
                    'options' => [
                        new MyRadioFormField(
                            'track',
                            MyRadioFormField::TYPE_TRACK,
                            [
                                'label' => 'Tracks'
                            ]
                        ),
                        new MyRadioFormField(
                            'artist',
                            MyRadioFormField::TYPE_ARTIST,
                            [
                                'label' => 'Artists'
                            ]
                        )
                    ]
                ]
            )
        )->addField(
            new MyRadioFormField(
                'notes',
                MyRadioFormField::TYPE_TEXT,
                [
                    'label' => 'Notes',
                    'explanation' => 'Optional. Enter notes about this change.',
                    'required' => false
                ]
            )
        );
    }

    public function getTracksEditForm()
    {
        return self::getTracksForm()
            ->setTitle('Edit Playlist')
            ->editMode(
                $this->getID(),
                [
                    'tracks.track' => $this->getTracks(),
                    'tracks.artist' => array_map(
                        function ($track) {
                            return $track->getArtist();
                        },
                        $this->getTracks()
                    )
                ]
            );
    }

    public static function getForm()
    {
        return (
            new MyRadioForm(
                'itones_playlistedit',
                'iTones',
                'configurePlaylist',
                [
                    'title' => 'Configure Jukebox Playlist'
                ]
            )
        )->addField(
            new MyRadioFormField(
                'title',
                MyRadioFormField::TYPE_TEXT,
                [
                    'label' => 'Name',
                    'explanation' => 'Name the playlist. I named my last playlist Scott.',
                    'required' => true
                ]
            )
        )->addField(
            new MyRadioFormField(
                'description',
                MyRadioFormField::TYPE_BLOCKTEXT,
                [
                    'label' => 'Description',
                    'explanation' => 'What is this playlist even for?',
                    'required' => false
                ]
            )
        );
    }

    public function getEditForm()
    {
        return self::getForm()
            ->setTitle('Configure Playlist')
            ->editMode(
                $this->getID(),
                [
                    'title' => $this->getTitle(),
                    'description' => $this->getDescription()
                ]
            );
    }

    /**
     * Return the MyRadio_Tracks that belong to this playlist
     * @return Array of MyRadio_Track objects
     */
    public function getTracks()
    {
        if (empty($this->tracks)) {
            $items = self::$db->fetchColumn(
                'SELECT trackid FROM jukebox.playlist_entries WHERE playlistid=$1
                AND revision_removed IS NULL
                ORDER BY entryid',
                [$this->playlistid]
            );

            foreach ($items as $id) {
                $this->tracks[] = MyRadio_Track::getInstance($id);
            }
        }

        return $this->tracks;
    }

    /**
     * Get the Title of the Playlist
     * @return String
     */
    public function getTitle()
    {
        return $this->title;
    }

    /**
     * Get the unique playlistid of the Playlist
     * @return String
     */
    public function getID()
    {
        return $this->playlistid;
    }

    /**
     * Get the long description of the Playlist
     * @return string
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * Get the current Revision ID of the Playlist
     * @return int
     */
    public function getRevisionID()
    {
        return $this->revisionid;
    }

    /**
     * Takes a lock on this playlist - stores a notification to all other systems that it should not be edited.
     *
     * @param String $lockstr If you already have a lock, put it here. It will be renewed if it is still valid.
     * @param MyRadio_User $user The user that has acquired the lock. Defaults to current user. Required for CLI requests.
     * This String will be invalidated by the update.
     *
     * @return bool|String false if the lock is not available, or a sha1 that proves ownership of the lock.
     * No, the hash isn't all that fancy, but it prevents people being stupid. Write operations require this String.
     */
    public function acquireOrRenewLock($lockstr = null, MyRadio_User $user = null)
    {
        if ($user === null) {
            $user = MyRadio_User::getInstance();
        }
        //Acquire a lock on the lock row - we don't want someone else acquiring a lock while we are!
        self::$db->query('BEGIN');
        self::$db->query('SELECT * FROM jukebox.playlists WHERE playlistid=$1 FOR UPDATE', [$this->getID()], true);

        //Refresh the local lock information - threads using this could have been running for a *while*
        $this->refreshLockInformation();

        if ($this->locktime >= time() - Config::$playlist_lock_time) {
            //There's a lock in place. Is it held by this client?
            if ($lockstr !== $this->generateLockKey($this->lock, $this->locktime)) {
                //It is not. Return false.
                return false;
            }
            //It's held by this user, we can update it.
        }
        //Or, if there isn't an active lock
        $locktime = time();
        self::$db->query('UPDATE jukebox.playlists SET lock=$1, locktime=$2 WHERE playlistid=$3', [$user->getID(), $locktime, $this->getID()], true);
        self::$db->query('COMMIT'); //This releases the lock
        $this->refreshLockInformation();

        return $this->generateLockKey($user, $locktime);
    }

    /**
     * Release your lock on this Playlist
     * @param String $lockstr
     */
    public function releaseLock($lockstr)
    {
        if ($this->validateLock($lockstr)) {
            self::$db->query('UPDATE jukebox.playlists SET locktime=NULL WHERE playlistid=$1', [$this->getID()]);
        }
    }

    /**
     * Updates the locally stored Lock information to ensure it is up to date
     */
    private function refreshLockInformation()
    {
        $result = self::$db->fetchOne('SELECT lock, locktime FROM jukebox.playlists WHERE playlistid=$1', [$this->getID()]);
        $this->lock = empty($result['lock']) ? null : MyRadio_User::getInstance($result['lock']);
        $this->locktime = (int) $result['locktime'];
    }

    /**
     * Generates a key to the provided lock
     * @param MyRadio_User $lock
     * @param int $locktime
     * @return String
     */
    private function generateLockKey(MyRadio_User $lock, $locktime)
    {
        return sha1('myuryitoneslockkey' . $lock->__toString() . $locktime . $this->getID());
    }

    /**
     * Returns if the provided Lock string is valid for this Playlist
     * @param String $lockstr
     * @return bool
     */
    public function validateLock($lockstr)
    {
        $this->refreshLockInformation();

        return $lockstr === $this->generateLockKey($this->lock, $this->locktime);
    }

    /**
     * Update the Tracks that belong to this playlist.
     *
     * It gets a list of all tracks in the Playlist, then iterates over each Track in $tracks
     * - If the Track is in the existing list, remove it from the temporary list
     * - If the Track is not in the list, INSERT it into the database from the current revision
     *
     * Once that's done, go over every Track still in the temporary list and remove them from the Playlist
     *
     * @param MyRadio_Track[] $tracks Tracks to put in the playlist.
     * @param String $lockstr The string that provides Write access to this Playlist. Acquired from acquireLock();
     * @param String $notes Optional. A textual commit message about the change.
     *
     * @todo Push these changes to the playlist files on playoutsvc.ury.york.ac.uk. This should probably be a MyRadioDaemon
     * configured to run only on that server.
     */
    public function setTracks($tracks, $lockstr, $notes = null, MyRadio_User $user = null)
    {
        if ($user === null) {
            $user = MyRadio_User::getInstance();
        }
        //Remove duplicates
        $tracks = array_unique($tracks);
        $old_list = $this->getTracks();

        //Check if anything has actually changed
        if ($tracks == $old_list) {
            return;
        }

        //Okay, it has. They'll need a lock to go any further
        if (!$this->validateLock($lockstr)) {
            throw new MyRadioException('You do not have a valid lock on this playlist.');
        }

        $new_additions = [];

        foreach ($tracks as $track) {
            $key = array_search($track, $old_list);
            if ($key === false) {
                //This is a new addition
                $new_additions[] = $track;
            } else {
                //This is an existing item
                unset($old_list[$key]);
            }
        }

        //Cool, now we know what needs to be done.
        self::$db->query('BEGIN');
        $revisionid = $this->getRevisionID() + 1;
        //Get the new revision ID
        self::$db->query(
            'INSERT INTO jukebox.playlist_revisions (playlistid, revisionid, author, notes)
            VALUES ($1, $2, $3, $4) RETURNING revisionid',
            [$this->getID(), $revisionid, $user->getID(), $notes],
            true
        );
        //Add new tracks
        foreach ($new_additions as $track) {
            if (empty($track)) {
                continue;
            }
            self::$db->query(
                'INSERT INTO jukebox.playlist_entries (playlistid, trackid, revision_added) VALUES ($1, $2, $3)',
                [$this->getID(), $track->getID(), $revisionid],
                true
            );
        }
        //Remove old tracks
        foreach ($old_list as $track) {
            if ($track instanceof MyRadio_Track) {
                self::$db->query(
                    'UPDATE jukebox.playlist_entries SET revision_removed=$1 WHERE playlistid=$2 AND trackid=$3
                    AND revision_removed IS NULL',
                    [$revisionid, $this->getID(), $track->getID()],
                    true
                );
            }
        }
        //All is happy. Commit!
        self::$db->query('COMMIT');
        $this->tracks = $tracks;
        $this->revisionid = $revisionid;
        $this->updateCacheObject();
    }

    /**
    * Update the title
    * @param String $title
    */
    public function setTitle($title)
    {
        self::$db->query('UPDATE jukebox.playlists SET title=$1 WHERE playlistid=$2', [$title, $this->getID()]);
        $this->title = $title;
        $this->updateCacheObject();
    }

    /**
    * Update the description
    * @param String $description
    */
    public function setDescription($description)
    {
        self::$db->query('UPDATE jukebox.playlists SET description=$1 WHERE playlistid=$2', [$description, $this->getID()]);
        $this->description = $description;
        $this->updateCacheObject();
    }

    /**
     * Get an array of all Playlists
     * @return Array of iTones_Playlist objects
     */
    public static function getAlliTonesPlaylists()
    {
        self::wakeup();
        $result = self::$db->fetchColumn('SELECT playlistid FROM jukebox.playlists ORDER BY title');

        return self::resultSetToObjArray($result);
    }

    /**
     * Uses weighted playout values to select a random Playlist, returning it.
     * @return iTones_Playlist
     */
    public static function getPlaylistFromWeights()
    {
        self::wakeup();

        $result = self::$db->fetchAll(
            'SELECT playlists.playlistid AS item, MAX(playlist_availability.weight) AS weight
                FROM jukebox.playlists, jukebox.playlist_availability, jukebox.playlist_timeslot
                WHERE playlists.playlistid=playlist_availability.playlistid
                    AND playlist_availability.playlist_availability_id=playlist_timeslot.playlist_availability_id
                    AND start_time <= "time"(NOW()) AND end_time >= "time"(NOW())
                    AND (day=EXTRACT(DOW FROM NOW()) OR (EXTRACT(DOW FROM NOW())=0 AND day=7))
                GROUP BY playlists.playlistid'
        );

        return self::getInstance(CoreUtils::biasedRandom($result));
    }

    /**
     * Find out what Playlists have this Track in them, if any
     * @param MyRadio_Track $track The track to search for
     * @return Array One or more iTones_Playlists, each of which contain $track
     */
    public static function getPlaylistsWithTrack(MyRadio_Track $track)
    {
        $result = self::$db->fetchColumn(
            'SELECT playlistid FROM jukebox.playlist_entries WHERE trackid=$1
            AND revision_removed IS NULL',
            [$track->getID()]
        );

        return self::resultSetToObjArray($result);
    }

    public static function create($title, $description)
    {
        $id = str_replace(' ', '-', $title);
        $id = strtolower(preg_replace('/[^a-z0-9-]/i', '', $id));
        self::$db->query(
            'INSERT INTO jukebox.playlists (playlistid, title, description)
            VALUES ($1, $2, $3)',
            [$id, $title, $description]
        );

        return self::getInstance($id);
    }

    /**
     * Returns an array of key information, useful for Twig rendering and JSON requests
     * @todo Expand the information this returns
     * @return Array
     */
    public function toDataSource()
    {
        return [
            'title' => $this->getTitle(),
            'playlistid' => $this->getID(),
            'description' => $this->getDescription(),
            'edittrackslink' => [
                'display' => 'icon',
                'value' => 'folder-open',
                'title' => 'Edit Tracks in this playlist',
                'url' => CoreUtils::makeURL('iTones', 'editPlaylist', ['playlistid' => $this->getID()])
            ],
            'configurelink' => [
                'display' => 'icon',
                'value' => 'wrench',
                'title' => 'Alter playlist settings',
                'url' => CoreUtils::makeURL('iTones', 'configurePlaylist', ['playlistid' => $this->getID()])
            ],
            'revisionslink' => [
                'display' => 'icon',
                'value' => 'clock',
                'title' => 'View revision history',
                'url' => CoreUtils::makeURL('iTones', 'viewPlaylistHistory', ['playlistid' => $this->getID()])
            ]
        ];
    }
}
