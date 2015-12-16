<?php

/**
 * This file provides the iTones_PlaylistRevision class for MyRadio - Contains history of an iTones_Playlist.
 */
namespace MyRadio\iTones;

use MyRadio\MyRadioException;
use MyRadio\MyRadio\CoreUtils;
use MyRadio\MyRadio\URLUtils;
use MyRadio\ServiceAPI\MyRadio_User;
use MyRadio\ServiceAPI\MyRadio_Track;

/**
 * The iTones_PlaylistRevision class helps to manage previous versions of an iTones_Playlist.
 */
class iTones_PlaylistRevision extends iTones_Playlist
{
    /**
     * When this revision was created.
     *
     * @var int
     */
    private $timestamp;

    /**
     * Who created this revision.
     *
     * @var MyRadio_User
     */
    private $author;

    /**
     * A commit message about the change.
     *
     * @var string
     */
    private $notes;

    /**
     * Initiates the PlaylistRevision variables.
     *
     * @param string $id $playlistid~$revisionid
     */
    protected function __construct($id)
    {
        list($playlistid, $revisionid) = explode('~', $id);
        parent::__construct($playlistid);

        $result = self::$db->fetchOne(
            'SELECT * FROM jukebox.playlist_revisions
            WHERE playlistid=$1 AND revisionid=$2 LIMIT 1',
            [$playlistid, $revisionid]
        );
        if (empty($result)) {
            throw new MyRadioException('The specified iTones Playlist Revision does not seem to exist', 404);

            return;
        }

        $this->revisionid = $revisionid;
        $this->author = MyRadio_User::getInstance($result['author']);
        $this->notes = $result['notes'];
        $this->timestamp = strtotime($result['timestamp']);

        $items = self::$db->fetchColumn(
            'SELECT trackid FROM jukebox.playlist_entries WHERE playlistid=$1
            AND revision_added <= $2 AND (revision_removed >= $2 OR revision_removed IS NULL)
            ORDER BY entryid',
            [$this->getID(), $this->getRevisionID()]
        );

        foreach ($items as $id) {
            $this->tracks[] = MyRadio_Track::getInstance($id);
        }
    }

    /**
     * Return the MyRadio_Tracks that belong to this playlist.
     *
     * @return array of MyRadio_Track objects
     */
    public function getTracks()
    {
        return $this->tracks;
    }

    public function getAuthor()
    {
        return $this->author;
    }

    public function getNotes()
    {
        return $this->notes;
    }

    public function getTimestamp()
    {
        return $this->timestamp;
    }

    public function getRevisionID()
    {
        return $this->revisionid;
    }

    /**
     * Prevents idiots attempting to edit this revision.
     */
    public function acquireOrRenewLock($lockstr = null, MyRadio_User $user = null)
    {
        throw new MyRadioException('You can\'t lock an archived playlist revision, poopyhead!');
    }

    /**
     * Prevents idiots attempting to edit this revision.
     */
    public function setTracks($tracks, $lockstr = null, $notes = null)
    {
        throw new MyRadioException('You can\'t lock an archived playlist revision, poopyhead!');
    }

    public static function getAllRevisions($playlistid)
    {
        $data = [];
        foreach (self::$db->fetchColumn(
            'SELECT revisionid FROM jukebox.playlist_revisions WHERE playlistid=$1',
            [$playlistid]
        ) as $revisionid) {
            $data[] = self::getInstance($playlistid.'~'.$revisionid);
        }

        return $data;
    }

    /**
     * Returns an array of key information, useful for Twig rendering and JSON requests.
     *
     * @todo Expand the information this returns
     *
     * @return array
     */
    public function toDataSource()
    {
        return [
            'revisionid' => $this->getRevisionID(),
            'timestamp' => CoreUtils::happyTime($this->getTimestamp()),
            'notes' => $this->getNotes(),
            'author' => $this->getAuthor()->getName(),
            'viewtrackslink' => [
                'display' => 'icon',
                'value' => 'folder-open',
                'title' => 'View Tracks in this playlist revision',
                'url' => URLUtils::makeURL(
                    'iTones',
                    'viewPlaylistRevision',
                    [
                        'playlistid' => $this->getID(),
                        'revisionid' => $this->getRevisionID(),
                    ]
                ),
            ],
            'restorelink' => [
                'display' => 'icon',
                'value' => 'retweet',
                'title' => 'Restore this revision',
                'url' => URLUtils::makeURL(
                    'iTones',
                    'restorePlaylistRevision',
                    [
                        'playlistid' => $this->getID(),
                        'revisionid' => $this->getRevisionID(),
                    ]
                ),
            ],
        ];
    }
}
