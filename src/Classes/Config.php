<?php
/**
 * This file provides the Config class for MyURY
 * @package MyURY_Core
 */

/**
 * Stores configuration settings
 *
 * @author Lloyd Wallis <lpw@ury.org.uk>
 * @version 20130711
 * @package MyURY_Core
 */
final class Config {
  /**
   * The ID of the "MyURY" Service. This *should* be the only Service, and therefore never change.
   * It's technically a remnant of the originally slightly overengineered modularisation structure.
   * @var int
   */
  public static $service_id = 1;
  /**
   * The Module to assume if one is not explicitly provided. This is usually the MyURY module, but if you're feeling
   * special, you can make Stats or Library the default...
   * @var String
   */
  public static $default_module = 'MyURY';
  /**
   * The Action to assume if one is not explicitly provided. This is usually default, but if you're feeling special,
   * you could make it index or home, but it'd break a lot of stuff.
   * @var String
   */
  public static $default_action = 'default';
  /**
   * The hostname of the PostgreSQL database server
   * @var String
   */
  public static $db_hostname    = 'localhost';
  /**
   * The username to use connecting to the PostgreSQL database server
   * @var String
   */
  public static $db_user        = 'root';
  /**
   * The password to use connecting to the PostgreSQL database server
   * @var String
   */
  public static $db_pass        = 'password';
  /**
   * The public timezone of the installation
   * @todo Full review and ensure UTC is used where appropriate (i.e. Scheduler)
   * @var String
   */
  public static $timezone       = 'Europe/London';
  
  /**
   * The base path of the MyURY installation
   * @var String
   */
  public static $base_path       = '/usr/local/www/myury/src';

  /**
   * The base URL of the MyURY installation
   * @var String
   */
  public static $base_url       = '//ury.org.uk/myury/';
  
  /**
   * The base URL of Shibbobleh - it has CSS and JS resources used by MyURY
   * @var String
   */
  public static $shib_url       = '//ury.org.uk/portal/';
  
  /**
   * Whether nice URL rewrites are enabled
   * If true, then urls will be myury/[module]/[action]
   * If false, then urls will be myury/?module=[module]&action=[action]
   * @var boolean
   */
  public static $rewrite_url    = true;
  
  /**
   * Whether to enable the Caching system
   * Development value: false
   * Production value: true
   * @var boolean
   */
  public static $cache_enable   = true;
  /**
   * The name of the class that will provide the MyURY Caching mechanism.
   * Must be the name of a valid class located in classes/[classname].php and implements CacheProvider
   * @var String
   */
  public static $cache_provider = 'APCProvider';
  /**
   * How long MyURY_Track items should be cached before the data is invalidated. This is Configurable as due to a lot
   * of external edit sources, it is reasonable to asssume the cache may become stale due to other systems.
   * @var int
   */
  public static $cache_track_timeout = 7200; //2 hours
  
  /**
   * Whether MyURY errors should be displayed in the browser. If this is set to false, users with the
   * AUTH_SHOWERRORS permission will still see errors.
   * Development value: true
   * Production value: false
   * @var boolean
   */
  public static $display_errors = false;
  
  /**
   * Whether MyURY Exceptions should be emailed to Computing
   * @var boolean
   */
  public static $email_exceptions = true;
  
  /**
   * Prevent an exception surge by failing if this many are thrown.
   * @var int
   */
  public static $exception_limit = 10;
  
  /**
   * Whether template debugging should be enabled
   * Development value: true
   * Production value: false
   * @var boolean
   */
  public static $template_debug = true;
  
  /**
   * The default number of results from an AJAX Search Query
   * This can be overriden on a per-request basis
   * @var int
   */
  public static $ajax_limit_default = 10;
  
  /**
   * The photoid to use for a Joined URY Timeline Event
   * @var int
   */
  public static $photo_joined = 1;
  /**
   * The photoid to use for a Gained Officership URY Timeline Event
   * @var int
   */
  public static $photo_officership_get = 2;
  /**
   * The photoid to use for a Lost Officership URY Timeline Event
   * @var int
   */
  public static $photo_officership_down = 3;
  /**
   * The photoid to use for a Got Show URY Timeline Event
   * @var int
   */
  public static $photo_show_get = 4;
  /**
   * The photoid to use for a Got Award URY Timeline Event
   * @var int
   */
  public static $photo_award_get = 5;
  
  /**
   * The id of the news feed to use for news events
   * @var int
   */
  public static $news_feed = 1;

  /**
   * The id of the news feed to use for presenter infomation
   * @var int
   */
  public static $piss_feed = 4;

  /**
   * The location of the Memcached server used for the Website.
   * This is so it can be cleared where necessary.
   * @var String
   */
  public static $django_cache_server = 'localhost';
  
  /**
   * The path to the motion Webcam logs. This must be a file path, but may be NFS/Samba mounter
   * @var String
   */
  public static $webcam_archive_path = '/home/motion/videos';
  
  /**
   * The path to store the original, unencoded copies of URYPlayer Podcasts.
   * The originals are archived here for future reencoding.
   * @var String
   */
  public static $podcast_archive_path = '/music/podcasts';
  
  /**
   * The URL where media should be stored. Used for podcasts, banners and images.
   * @var String
   */
  public static $public_media_path = '/home/django/virtualenvs/urysite/assets/media';
  /**
   * This is the HTTP-accessible version of the above directory. Should be absolute or relative to domain, but not
   * protocol-specific, e.g. /media or //ury.org.uk/media
   * @var String
   */
  public static $public_media_uri = '/media';
  /**
   * The full web address to the image that will be served on a member's profile page if they do not have a profile
   * photo. The original value, /static/img/default_show_player.png is the main website's placeholder for shows
   * @var String
   */
  public static $default_person_uri = '/static/img/default_show_player.png';
  
  /**
   * The full web address of the image that will be shown for a vacant officer position
   * @var String
   */
  public static $vacant_officer_uri = '/media/image_meta/MyURYImageMetadata/32.jpeg';
  
  /**
   * The file system path to the Central Database. Must be absolute. Can not be smb://, but may be a network share
   * mounted to the file system mountpoint.
   * @var String
   */
  public static $music_central_db_path = '/music';
  
  /**
   * The Samba File Share path to the Central Database. This is used for BAPS compatibility features.
   * @var String
   */
  public static $music_smb_path = '\\\\musicstore.ury.york.ac.uk';
  
  /**
   * A path to temporarially store uploaded audio files. Recommend somewhere in /tmp, MyURY needs full r/w access to it.
   * @var String
   */
  public static $audio_upload_tmp_dir = '/tmp/myuryaudiouploadcache';
  
  /**
   * The API key to access last.fm's resources.
   * @var String
   */
  public static $lastfm_api_key;
  
  /**
   * The API Secret to write last.fm's resources.
   * @var String
   */
  public static $lastfm_api_secret;
  
  /**
   * The array of different versions of tracks one can expect to find in the Central Database. Used for file servers
   * and other systems to ensure the file requested seems legit.
   * @var Array[String]
   */
  public static $music_central_db_exts = array('mp3', 'ogg', 'mp3.orig');
  
  /**
   * Mailing list to send reporting info to
   * @var String
   * @todo Make this point to a MyURY_List ID?
   */
  public static $reporting_list = 'alerts.myury';
  
  /**
   * The IP/hostname of the iTones Liquidsoap Telnet Service
   * @var String
   */
  public static $itones_telnet_host = '144.32.64.167';
  /**
   * The port of the iTones Liquidsoap Telnet Service
   * @var int
   */
  public static $itones_telnet_port = 1234;
  
  /**
   * The IP/hostname of the Studio Selector Telnet Service
   * @var String
   */
  public static $selector_telnet_host = '144.32.64.167';
  
  /**
   * The port of the Studio Selector Telnet Service
   * @var int
   */
  public static $selector_telnet_port = 1354;
  
  /**
   * The path to the file that reports the state of the remote OB feeds
   * @var String
   */
  public static $ob_remote_status_file = '/music/ob_state.conf';
  
  /**** ERROR REPORTING ****/
  
  /**
   * The file to store MyURY Error Logs
   * @var String
   */
  public static $log_file = '/var/log/ury-org-uk/myury_errors.log';
  /**
   * A lock file on the MyURY Error Logs. Prevents email spam.
   * @var String
   */
  public static $log_file_lock = '/var/log/ury-org-uk/myury_errors.lock';
  /**
   * The email to send error reports to. This is different from reporting_email, which does statistical reports.
   * @var String
   */
  public static $error_report_email = 'alerts.myury';
  
  /**
   * The number of seconds an iTones Playlist lock is valid for before it expires.
   * @var int
   */
  public static $playlist_lock_time = 30;
  
  /**
   * The User that MyURY assumes when doing things as a background task
   * @var int Mr Website
   */
  public static $system_user = 779;
  
  /**
   * This key enables automated access to the YUSU CMS information about URY's members
   */
  public static $yusu_api_key;
  
  /**
   * The default college for new users that do not specify one.
   * 10 is Unknown.
   */
  public static $default_college = 10;
  
  /**
   * A path to the file system (preferably in /tmp) that the MyURY Daemon tools can have write access to. It stores
   * state information about the service that should not be permanent but presist after a reload of the service.
   * @var String
   */
  public static $daemon_lock_file = '/tmp/myury_daemon.lock';
  
  /**
   * The root URL to URY's API
   * 
   * Must be absolute.
   * @var String
   */
  public static $api_url = 'https://ury.org.uk/api';
  
  /**
   * The URL prefix to URY's webcam
   * 
   * Must be absolute. With trailing /
   * @var String
   */
  public static $webcam_prefix = '//ury.org.uk/webcam/';

  /**
   * BRA Server
   * @var String
   */
  public static $bra_uri = 'https://urybsod.york.ac.uk/bra';
  
  /**
   * Relative path to the API. Must have trailing /
   * @var String
   */
  public static $api_uri = '/api/';
  
  /**
   * Relative path to the SIS plugins.
   * @var String
   */
  public static $sis_plugin_folder = 'Models/SIS/plugins';

  /**
   * Relative path to the SIS tabs.
   * @var String
   */
  public static $sis_tab_folder = 'Models/SIS/tabs';

/**
 * Studio data
 * name is the name that is shown if it is detected as the current output
 * authenticated_machines is an array of IP addresses which will have all rights in SIS, even if they are non-officer
 * colour is the colour of any alements identifying the studio. Any valid CSS color will work here
 * @var Array
 */
  public static $studios = array(
      array(
          'name' => 'Campus Jukebox',
          'authenticated_machines' => array(),
          'colour' => '#0F0'
      ),
      array(
          'name' => 'Studio 1',
          'authenticated_machines' => array('144.32.64.181', '144.32.64.183'),
          'colour' => 'red'
      ),
      array(
          'name' => 'Studio 2',
          'authenticated_machines' => array('144.32.64.184', '144.32.64.185'),
          'colour' => '#0044BA'
      ),
      array(
          'name' => 'Outside Broadcast',
          'authenticated_machines' => array(), //TODO: Add the OB Machines here
          'colour' => '#bb00dc'
      ),
  );

  /**
   * URY's Membership Fee
   * @var float
   */
  public static $membership_fee = 7.00;
  
  /**** DAEMON CONFIGURATION ****/
  public static $d_BAPSSync_enabled = false;
  public static $d_EmailQueue_enabled = true;
  public static $d_Fingerprinter_enabled = false;
  public static $d_LabelFinder_enabled = false;
  public static $d_MemberSync_enabled = false;
  public static $d_Playlists_enabled = true;
  public static $d_Podcast_enabled = true;
  public static $d_StatsGen_enabled = true;
  public static $d_Explicit_enabled = false;
  
  /**
   * The constructor doesn't do anything practical
   * 
   * By making the constructor private, even though it does not do anything, we are prohibiting code elsewhere from
   * creating instances of this class, making it essentially static
   */
  private function __construct() {}
  
  /**
   * "Public" config is the configuration variables that should be made exposed to JavaScript within the mConfig
   * object.
   * @return Array
   */
  public static function getPublicConfig() {
    return array(
        'ajax_limit_default' => self::$ajax_limit_default,
        'base_url' => self::$base_url,
        'rewrite_url' => self::$rewrite_url,
        'shib_url' => self::$shib_url,
        'timezone' => self::$timezone,
        'default_module' => self::$default_module,
        'default_action' => self::$default_action,
        'webcam_prefix' => self::$webcam_prefix,
        'bra_uri' => self::$bra_uri
    );
  }
}
