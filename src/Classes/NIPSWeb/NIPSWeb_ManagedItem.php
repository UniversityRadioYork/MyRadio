<?php
/**
 * This file provides the NIPSWeb_ManagedItem class for MyRadio - these are Jingles, Beds, Adverts and others of a similar
 * ilk
 * @package MyRadio_NIPSWeb
 */

/**
 * The NIPSWeb_ManagedItem class helps provide control and access to Beds and Jingles and similar not-PPL resources
 *
 * @version 20130601
 * @author Lloyd Wallis <lpw@ury.org.uk>
 * @package MyRadio_NIPSWeb
 * @uses \Database
 */
class NIPSWeb_ManagedItem extends ServiceAPI
{
    private $managed_item_id;

    private $managed_playlist;

    private $folder;

    private $title;

    private $length;

    private $bpm;

    private $expirydate;

    private $member;

    /**
     * Initiates the ManagedItem variables
     * @param int $resid The ID of the managed resource to initialise
     * @param NIPSWeb_ManagedPlaylist $playlistref If the playlist is requesting this item, then pass the playlist object
     * @todo Length, BPM
     * @todo Seperate Managed Items and Managed User Items. The way they were implemented was a horrible hack, for which
     * I am to blame. I should go to hell for it, seriously - Lloyd
     */
    protected function __construct($resid, $playlistref = null)
    {
        $this->managed_item_id = $resid;
        //*dies*
        $result = self::$db->fetchOne(
            'SELECT manageditemid, title, length, bpm, NULL AS folder, memberid, expirydate, managedplaylistid
            FROM bapsplanner.managed_items WHERE manageditemid=$1
            UNION SELECT manageditemid, title, length, bpm, managedplaylistid AS folder, NULL AS memberid, NULL AS expirydate,
            NULL as managedplaylistid
            FROM bapsplanner.managed_user_items WHERE manageditemid=$1
            LIMIT 1',
            [$resid]
        );

        if (empty($result)) {
            throw new MyRadioException('The specified NIPSWeb Managed Item or Managed User Item does not seem to exist');

            return;
        }

        $this->managed_playlist = empty(
            $result['managedplaylistid']) ? null :
                (($playlistref instanceof NIPSWeb_ManagedPlaylist) ? $playlistref :
                    NIPSWeb_ManagedPlaylist::getInstance($result['managedplaylistid'])
                );
        $this->folder = $result['folder'];
        $this->title = $result['title'];
        $this->length = strtotime('1970-01-01 '.$result['length']);
        $this->bpm = (int) $result['bpm'];
        $this->expirydate = strtotime($result['expirydate']);
        $this->member = empty($result['memberid']) ? null : MyRadio_User::getInstance($result['memberid']);
    }

    /**
     * Get the Title of the ManagedItem
     * @return String
     */
    public function getTitle()
    {
        return $this->title;
    }

    /**
     * Get the unique manageditemid of the ManagedItem
     * @return int
     */
    public function getID()
    {
        return $this->managed_item_id;
    }

    /**
     * Get the length of the ManagedItem, in seconds
     * @todo Not Implemented as Length not stored in DB
     * @return int
     */
    public function getLength()
    {
        return $this->length;
    }

    /**
     * Get the path of the ManagedItem
     * @param String $ext One of the supported file types
     * @return string
     */
    public function getPath($extension = 'mp3')
    {
        return Config::$music_central_db_path.'/'.($this->managed_playlist ? $this->managed_playlist->getFolder() : $this->folder).'/'.$this->getID().'.'.$extension;
    }

    public function getFolder()
    {
        $dir = Config::$music_central_db_path.'/'.($this->managed_playlist ? $this->managed_playlist->getFolder() : $this->folder);
        if (!is_dir($dir)) {
            if (!mkdir($dir, 0777, true)) {
                return false;
            }
        }

        return $dir;
    }

    /**
     * Returns an array of key information, useful for Twig rendering and JSON requests
     * @todo Expand the information this returns
     * @return Array
     */
    public function toDataSource()
    {
        return [
            'type' => 'aux', //Legacy NIPSWeb Views
            'summary' => $this->getTitle(), //Again, freaking NIPSWeb
            'title' => $this->getTitle(),
            'managedid' => $this->getID(),
            'length' => CoreUtils::happyTime($this->getLength() > 0 ? $this->getLength() : 0, true, false),
            'trackid' => $this->getID(),
            'recordid' => 'ManagedDB', //Legacy NIPSWeb Views
            'auxid' => 'managed:' . $this->getID() //Legacy NIPSWeb Views
        ];
    }

    public static function cacheItem($tmp_path)
    {
        if (!isset($_SESSION['myury_nipsweb_file_cache_counter'])) {
            $_SESSION['myury_nipsweb_file_cache_counter'] = 0;
        }
        if (!is_dir(Config::$audio_upload_tmp_dir)) {
            mkdir(Config::$audio_upload_tmp_dir);
        }

        $filename = session_id() . '-' . ++$_SESSION['myury_nipsweb_file_cache_counter'] . '.mp3';

        move_uploaded_file($tmp_path, Config::$audio_upload_tmp_dir . '/' . $filename);

        $getID3 = new getID3;
        $fileInfo = $getID3->analyze(Config::$audio_upload_tmp_dir . '/' . $filename);

        //The entire $fileInfo array will break Session.
        $_SESSION['uploadInfo'][$filename] = [
            'fileformat' => $fileInfo['fileformat'],
            'playtime_seconds' => $fileInfo['playtime_seconds']
        ];

        // File quality checks
        if ($fileInfo['audio']['bitrate'] < 192000) {
            return ['status' => 'FAIL', 'error' => 'Bitrate is below 192kbps.', 'fileid' => $filename, 'bitrate' => $fileInfo['audio']['bitrate']];
        }
        if (strpos($fileInfo['audio']['channelmode'], 'stereo') === false) {
            return ['status' => 'FAIL', 'error' => 'Item is not stereo.', 'fileid' => $filename, 'channelmode' => $fileInfo['audio']['channelmode']];
        }

        return [
            'fileid' => $filename,
        ];
    }

    public static function storeItem($tmpid, $title)
    {
        $options = [
            'title' => $title,
            'expires' => $_REQUEST['expires'],
            'auxid' => $_REQUEST['auxid'],
            'duration' => $_SESSION['uploadInfo'][$tmpid]['playtime_seconds'],
        ];

        $item = self::create($options);

        if (!$item) {
            //Database transaction failed.
            return ['status' => 'FAIL', 'error' => 'A database kerfuffle occured.', 'fileid' => $_REQUEST['fileid']];
        }

        /**
         * Store three versions of the track:
         * 1- 192kbps MP3 for BAPS and Chrome/IE
         * 2- 192kbps OGG for Safari/Firefox
         * 3- Original file for potential future conversions
         */
        $tmpfile = Config::$audio_upload_tmp_dir.'/'.$tmpid;

        if (!$item->getFolder()) {
            //Creating folders failed.
            return ['status' => 'FAIL', 'error' => 'Folders could not be created.', 'fileid' => $_REQUEST['fileid']];
        }
        $dbfile = $item->getFolder().'/'.$item->getID();

        //Convert it with ffmpeg
        // BAPS needs stdout > to file
        shell_exec("nice -n 15 ffmpeg -i '$tmpfile' -ab 192k -f mp3 - > '{$dbfile}.mp3'");
        shell_exec("nice -n 15 ffmpeg -i '$tmpfile' -acodec libvorbis -ab 192k '{$dbfile}.ogg'");
        rename($tmpfile, $dbfile.'.'.$_SESSION['uploadInfo'][$tmpid]['fileformat'].'.orig');

        if (!file_exists($dbfile.'.mp3') || !file_exists($dbfile.'.ogg')) {
            //Conversion failed!
            return ['status' => 'FAIL', 'error' => 'Conversion with ffmpeg failed.', 'fileid' => $_REQUEST['fileid']];
        } elseif (!file_exists($dbfile.'.'.$_SESSION['uploadInfo'][$tmpid]['fileformat'].'.orig')) {
            return ['status' => 'FAIL', 'error' => 'Could not move file to library.', 'fileid' => $_REQUEST['fileid']];
        }

        NIPSWeb_BAPSUtils::linkCentralLists($item);

        return ['status' => 'OK', 'title' => $title];
    }

    /**
     * Create a new NIPSWEB_ManagedItem with the provided options
     * @param Array $options
     * title (required): Title of the item.
     * duration (required): Duration of the item, in seconds
     * auxid (required): The auxid of the playlist
     * bpm: The beats per minute of the item
     * expires: The expiry date of the item
     * @return NIPSWEB_ManagedItem a shiny new NIPSWEB_ManagedItem with the provided options
     * @throws MyRadioException
     */
    public static function create($options)
    {
        self::wakeup();

        $required = ['title', 'duration', 'auxid'];
        foreach ($required as $require) {
            if (empty($options[$require])) {
                throw new MyRadioException($require.' is required to create an Item.', 400);
            }
        }
        //BPM null
        if (empty($options['bpm'])) {
            $options['bpm'] = null;
        }
        //Expires null
        if (empty($options['expires'])) {
            $options['expires'] = null;
        }

        //Decode the auxid to figure out what/where we're adding
        if (strpos($options['auxid'], 'user-') !== false) {
            //This is a personal resource
            $path = str_replace('user-', 'membersmusic/', $options['auxid']);
            $result = self::$db->query(
                'INSERT INTO bapsplanner.managed_user_items (managedplaylistid, title, length, bpm)
                VALUES ($1, $2, $3, $4) RETURNING manageditemid',
                [
                    $path,
                    trim($options['title']),
                    CoreUtils::intToTime($options['duration']),
                    $options['bpm'],
                ]
            );
        } else {
            //This is a central resource
            $result = self::$db->fetchColumn(
                'SELECT managedplaylistid FROM bapsplanner.managed_playlists WHERE managedplaylistid=$1 LIMIT 1',
                [str_replace('aux-', '', $options['auxid'])]
            );
            if (empty($result)) {
                throw new MyRadioException($options['auxid'].' is not a valid playlist!');
            }
            $playlistid = $result[0];

            $result = self::$db->query(
                'INSERT INTO bapsplanner.managed_items (managedplaylistid, title, length, bpm, expirydate, memberid)
                VALUES ($1, $2, $3, $4, $5, $6) RETURNING manageditemid',
                [
                    $playlistid,
                    trim($options['title']),
                    CoreUtils::intToTime($options['duration']),
                    $options['bpm'],
                    $options['expires'],
                    $_SESSION['memberid'],
                ]
            );
        }

        $id = self::$db->fetchAll($result);

        return self::getInstance($id[0]['manageditemid']);
    }
}
