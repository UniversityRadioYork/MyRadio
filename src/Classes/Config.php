<?php
/**
 * This file provides the Config class for MyRadio.
 */
namespace MyRadio;

/**
 * Stores configuration settings.
 */
final class Config
{
    /**
     * If true, MyRadio will open the setup wizard when accessed.
     * This should be false in production unless you want bad things to happen.
     *
     * @var bool
     */
    public static $setup = false;
    /**
     * The ID of the "MyRadio" Service. This *should* be the only Service, and therefore never change.
     * It's technically a remnant of the originally slightly overengineered modularisation structure.
     *
     * @var int
     */
    public static $service_id = 1;
    /**
     * The Module to assume if one is not explicitly provided. This is usually the MyRadio module, but if you're feeling
     * special, you can make Stats or Library the default...
     *
     * @var string
     */
    public static $default_module = 'MyRadio';
    /**
     * The Action to assume if one is not explicitly provided. This is usually default, but if you're feeling special,
     * you could make it index or home, but it'd break a lot of stuff.
     *
     * @var string
     */
    public static $default_action = 'default';
    /**
     * The hostname of the PostgreSQL database server.
     *
     * @var string
     */
    public static $db_hostname = 'localhost';
    public static $db_name = 'membership';
    /**
     * The username to use connecting to the PostgreSQL database server.
     *
     * @var string
     */
    public static $db_user = 'root';
    /**
     * The password to use connecting to the PostgreSQL database server.
     *
     * @var string
     */
    public static $db_pass = 'password';
    /**
     * The public timezone of the installation.
     *
     * @todo Full review and ensure UTC is used where appropriate (i.e. Scheduler)
     *
     * @var string
     */
    public static $timezone = 'Europe/London';

    /**
     * The base URL of the MyRadio installation.
     *
     * @var string
     */
    public static $base_url = '//ury.org.uk/myradio/';

    /**
     * The base URL of the schedule - has some JS resources from MyRadio.
     *
     * @var string
     */
    public static $schedule_url = '//ury.org.uk/schedule';

    /**
     * The base URL of the radio home pages.
     *
     * @var string
     */
    public static $website_url = '//ury.org.uk/';

    /**
     * Whether nice URL rewrites are enabled
     * If true, then urls will be myradio/[module]/[action]
     * If false, then urls will be myradio/?module=[module]&action=[action].
     *
     * @var bool
     */
    public static $rewrite_url = true;

    /**
     * Whether to enable the Caching system
     * Development value: false
     * Production value: true.
     *
     * @var bool
     */
    public static $cache_enable = true;
    /**
     * The name of the class that will provide the MyRadio Caching mechanism.
     * Must be the name of a valid class located in classes/[classname].php and implements CacheProvider.
     *
     * @var string
     */
    public static $cache_provider = '\MyRadio\MemcachedProvider';
    /**
     * If using the Memcached CacheProvider, set a list of servers to use here.
     * e.g. [['localhost', 11211]] (optionally, a third option, a weighting).
     */
    public static $cache_memcached_servers = [['localhost', 11211]];
    /**
     * How long ServiceAPI items should be cached for by default. Turn this down if you
     * get a lot of edits from other sources.
     *
     * @var int
     */
    public static $cache_default_timeout = 86400;

    /**
     * Whether MyRadio errors should be displayed in the browser. If this is set to false, users with the
     * AUTH_SHOWERRORS permission will still see errors.
     * Development value: true
     * Production value: false.
     *
     * @var bool
     */
    public static $display_errors = false;

    /**
     * Whether MyRadio Exceptions should be emailed to Computing.
     *
     * @var bool
     */
    public static $email_exceptions = true;

    /**
     * Prevent an exception surge by failing if this many are thrown.
     *
     * @var int
     */
    public static $exception_limit = 10;

    /**
     * Whether template debugging should be enabled
     * Development value: true
     * Production value: false.
     *
     * @var bool
     */
    public static $template_debug = true;

    /**
     * The default number of results from an AJAX Search Query
     * This can be overriden on a per-request basis.
     *
     * @var int
     */
    public static $ajax_limit_default = 25;

    /**
     * The photoid to use for a Joined URY Timeline Event.
     *
     * @var int
     */
    public static $photo_joined = 1;
    /**
     * The photoid to use for a Gained Officership URY Timeline Event.
     *
     * @var int
     */
    public static $photo_officership_get = 2;
    /**
     * The photoid to use for a Lost Officership URY Timeline Event.
     *
     * @var int
     */
    public static $photo_officership_down = 3;
    /**
     * The photoid to use for a Got Show URY Timeline Event.
     *
     * @var int
     */
    public static $photo_show_get = 4;
    /**
     * The photoid to use for a Got Award URY Timeline Event.
     *
     * @var int
     */
    public static $photo_award_get = 5;

    /**
     * The id of the news feed to use for news events.
     *
     * @var int
     */
    public static $news_feed = 1;

    /**
     * The id of the news feed to use for presenter infomation.
     *
     * @var int
     */
    public static $presenterinfo_feed = 4;

    /**
     * The path to the motion Webcam logs. This must be a file path, but may be NFS/Samba mounter.
     *
     * @var string
     */
    public static $webcam_archive_path = '/home/motion/videos';

    /**
     * The url of the webcam server status.
     *
     * @var string
     */
    public static $webcam_current_url;

    /**
     * The url of the webcam server setter.
     *
     * @var string
     */
    public static $webcam_set_url;

    /**
     * The path to store the original, unencoded copies of MyRadio Podcasts.
     * The originals are archived here for future reencoding.
     *
     * @var string
     */
    public static $podcast_archive_path = '/music/podcasts';

    /**
     * The file system path where media should be stored. Used for podcasts, banners and images.
     *
     * @var string
     */
    public static $public_media_path = '/home/django/virtualenvs/urysite/assets/media';
    /**
     * This is the HTTP-accessible version of the above directory. Should be absolute or relative to domain, but not
     * protocol-specific, e.g. /media or //ury.org.uk/media.
     *
     * @var string
     */
    public static $public_media_uri = '/media';

    /**
     * The full web address to the image that will be served for a show if there
     * is not a photo for that show.
     *
     * @var string
     */
    public static $default_show_uri = '/media/image_meta/ShowImageMetadata/22.png';

    /**
     * The full web address to the image that will be served outside of term time.
     *
     * @var string
     */
    public static $offair_uri = '/media/image_meta/ShowImageMetadata/offair.png';

    /**
     * The full web address to the image that will be served on a member's profile page if they do not have a profile
     * photo. The original value, /static/img/default_show_player.png is the main website's placeholder for shows.
     *
     * @var string
     */
    public static $default_person_uri = '/static/img/default_show_player.png';

    /**
     * The full web address of the image that will be shown for a vacant officer position.
     *
     * @var string
     */
    public static $vacant_officer_uri = '/media/image_meta/MyRadioImageMetadata/32.jpeg';

    /**
     * The full web address of a copy of the Presenters Contract.
     *
     * @note If this variable is empty, then contracts are disabled.
     *
     * @var string
     */
    public static $contract_uri = '';

    /**
     * The file system path to the Central Database. Must be absolute. Can not be smb://, but may be a network share
     * mounted to the file system mountpoint.
     *
     * @var string
     */
    public static $music_central_db_path = '/music';

    /**
     * The file to be played if the obit procedure is triggered.
     * This is only used if you use the MyRadio iTones Liquidsoap playout tools.
     *
     * @var string
     */
    public static $jukebox_obit_file = '/jukebox/OBIT.mp3';

    /**
     * The Samba File Share path to the Central Database.
     * This is used for BAPS compatibility features.
     *
     * If you are not URY, you will likely not need this setting.
     * However, if you have a studio audio playout tool that needs
     * samba paths to files stored in a database, set this here.
     * This data will then be stored in the public.baps_ table.
     *
     * @var string
     */
    public static $music_smb_path = '\\\\musicstore.ury.york.ac.uk';

    /**
     * A path to temporarily store uploaded audio files. Recommend somewhere in /tmp,
     * MyRadio needs full r/w access to it to enable Library management.
     *
     * @var string
     */
    public static $audio_upload_tmp_dir = '/tmp/myradioaudiouploadcache';

    /**
     * The maximum allowed size of a single Track upload, in MB.
     * Still bound by php.ini settings.
     *
     * @var string
     */
    public static $audio_upload_max_size = 15;

    /**
     * The API key to access last.fm's resources.
     *
     * You will need one of these to enable Library management.
     *
     * @var string
     */
    public static $lastfm_api_key;

    /**
     * The API Secret to write last.fm's resources.
     *
     * @var string
     */
    public static $lastfm_api_secret;

     /**
      * The last.fm nation of choice, at least for us. If using
      * this aspect of the code you probably want to change this bit.
      */
    public static $lastfm_geo = 'United+Kingdom';

    /**
     * The array of different versions of tracks one can expect to find in the
     * Central Database. Used for file servers and other systems to ensure
     * the file requested seems legit.
     *
     * @var Array[String]
     */
    public static $music_central_db_exts = ['mp3', 'ogg', 'mp3.orig'];

    /**
     * Mailing list to send reporting info to.
     *
     * @var string
     *
     * @todo Make this support non-MyRadio managed addresses
     */
    public static $reporting_list = 'alerts.myradio';

    /**
     * The IP/hostname of the iTones Liquidsoap Telnet Service.
     *
     * @var string
     */
    public static $itones_telnet_host = '144.32.64.167';
    /**
     * The port of the iTones Liquidsoap Telnet Service.
     *
     * @var int
     */
    public static $itones_telnet_port = 1234;

    /**
     * The maximum number of requests in one $itones_request_period per user.
     *
     * @var int
     */
    public static $itones_request_maximum = 5;

    /**
     * The period in which a user can use up to $itones_request_maximum requests.
     *
     * This is evaluated as a PostgreSQL INTERVAL: examples of valid values are
     * '1 hour', '5 minutes' or '10:00:00'.
     *
     * @var string
     */
    public static $itones_request_period = '1 hour';

    /**
     * The IP/hostname of the Studio Selector Telnet Service.
     *
     * @var string
     */
    public static $selector_telnet_host = '144.32.64.167';

    /**
     * The port of the Studio Selector Telnet Service.
     *
     * @var int
     */
    public static $selector_telnet_port = 1354;

    /**
     * The path to the file that reports the state of the remote OB feeds.
     *
     * @var string
     */
    public static $ob_remote_status_file = '/music/ob_state.conf';

    /**** ERROR REPORTING ****/

    /**
     * The file to store MyRadio Error Logs.
     *
     * @var string
     */
    public static $log_file = '/var/log/myradio/errors.log';
    /**
     * A lock file on the MyRadio Error Logs. Prevents email spam.
     *
     * @var string
     */
    public static $log_file_lock = '/tmp/myradio_errors.lock';
    /**
     * The email to send error reports to. This is different from reporting_list,
     * which does statistical reports, if enabled.
     *
     * @var string
     */
    public static $error_report_email = 'alerts.myradio';

    /**
     * The number of seconds an iTones Playlist lock is valid for before it expires.
     *
     * @var int
     */
    public static $playlist_lock_time = 30;

    /**
     * The User that MyRadio assumes when doing things as a background task.
     *
     * @var int Mr Website
     */
    public static $system_user = 779;

    /**
     * The URL for the SU page allowing people to pay and join the society.
     */
    public static $yusu_payment_url;

    /**
     * This key enables automated access to the YUSU CMS information about URY's members.
     *
     * This is literally only useful if you are URY.
     */
    public static $yusu_api_key;

    /**
     * The web address (up to the endpoint) where the YUSU API lives. It changes from time
     * to time so check that the API calls are actually succeeding now and then.
     */
    public static $yusu_api_website;

    /**
     * The default college for new users that do not specify one.
     * 1 is Unknown. (Unless you're URY, in which case it's 10 because legacy).
     */
    public static $default_college = 1;

    /**
     * A path to the file system (preferably in /tmp) that the MyRadio Daemon tools can have write access to. It stores
     * state information about the service that should not be permanent but presist after a reload of the service.
     *
     * @var string
     */
    public static $daemon_lock_file = '/tmp/myradio_daemon.lock';

    /**
     * The root URL to the API.
     *
     * Must be absolute.
     *
     * @var string
     */
    public static $api_url = '/api';

    /**
     * The URL prefix to URY's webcam.
     *
     * Must be absolute. With trailing /
     *
     * @var string
     */
    public static $webcam_prefix = '//ury.org.uk/webcam/';

    /**
     * Relative path to the API. Must have trailing /.
     *
     * @var string
     */
    public static $api_uri = '/api/';

    /**
     * Recaptcha settings. Used for password resets.
     *
     * http://recaptcha.net
     *
     * @var string
     */
    public static $recaptcha_public_key = 'YOUR_API_KEY';
    public static $recaptcha_private_key = 'YOUR_PRIVATE_KEY';

    /**
     * Array of tabs and plugins to be used by SIS. They will be loaded in order.
     */
    public static $sis_modules = [
        'presenterinfo',
        'messages',
        'schedule',
        'tracklist',
        'links',
        'selector',
        'webcam',
        'obit',
    ];

    /**
     * Array of spam strings to check messages for.
     *
     * @var string[]
     */
    public static $spam = [];

    /**
     * Array of social engineering strings to check messages for.
     *
     * @var string[]
     */
    public static $social_engineering_trigger = [];

    /**
     * Warning text to display on suspected social engineering attacks.
     *
     * @var string
     */
    public static $social_engineering_warning = 'Beware of Social Engineering, someone may be trying to spoil your show.
    Management and Computing will never send official communication through SIS.';

    /**
     * URL of the news provider.
     *
     * @var string
     */
    public static $news_provider = 'http://www.irn.co.uk/';

    /**
     * Host that the news provider must be accessed from.
     *
     * @var string
     */
    public static $news_proxy = '';

    /**
     * URY's Membership Fee.
     *
     * @var float
     */
    public static $membership_fee = 7.00;

    /**
     * If enabled, the Members' News feature on the home page is active.
     */
    public static $members_news_enable = false;

    /**
     * Authentication
     * LDAP requires the ldap plugin (net/php5-ldap)
     * The Authenticators are tried in order when completing user authentication
     * operations.
     */
    public static $authenticators = ['\MyRadio\MyRadio\MyRadioDefaultAuthenticator'];
    public static $auth_ldap_server = 'ldap://ldap.york.ac.uk';
    public static $auth_ldap_root = 'ou=people,ou=csrv,ou=nos,dc=york,dc=ac,dc=uk';
    public static $auth_db_user = '';
    public static $auth_db_pass = '';

    /**
     * Optional eduroam auth domain (probably .ac.uk).
     *
     * @var string
     */
    public static $eduroam_domain = 'york.ac.uk';
    public static $auth_ldap_friendly_name = 'IT Services';
    public static $auth_ldap_reset_url = 'https://idm.york.ac.uk/';

    /**
     * Email configuration.
     */

    //All email domains handled by MyRadio
    public static $local_email_domains = [];

    /**
     * Primary email domain. MyRadio will send emails from this domain.
     *
     * @var string
     */
    public static $email_domain = 'ury.org.uk';

    /**
     * If true, users will be bound to a single Authenticator. Users whose
     * authenticator is NULL will be asked to set an Authenticator after login.
     *
     * If it is false, all authenticators will be valid for all users.
     *
     * @var bool
     */
    public static $single_authenticator = false;

    /**
     * If false, MyRadioDefaultAuthenticator will never pass, passwords will not
     * be set for new users, and the Change Password functionality will not be
     * available.
     *
     * @var bool
     */
    public static $enable_local_passwords = true;

    /**
     * The number of days before the start of the academic year when accounts are inactivated
     * The current choice should mean it resets results week.
     */
    public static $account_expiry_before = 49;

    /**
     * The email list to send Obit activation notifications to.
     */
    public static $obit_list_id = 36;

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

    /**** STRINGS ****/
    public static $short_name = 'URY';
    public static $long_name = 'University Radio York';
    public static $founded = '1967';
    public static $facebook = 'https://www.facebook.com/URY1350';

    /**** SIGNUP EMAILS ****/
    public static $welcome_email_sender_memberid = NULL;
    public static $welcome_email = <<<EOT

   This is a welcome email. You can use #NAME to get the user's first name.

EOT;
    public static $account_email = <<<EOT

   This is an email to give a new member their account details.
   You can use #NAME to get the user's first name.
   You can use #USER and #PASS to tell them their newly created login details.

EOT;



    /**
     * The constructor doesn't do anything practical.
     *
     * By making the constructor private, even though it does not do anything, we are prohibiting code elsewhere from
     * creating instances of this class, making it essentially static
     */
    private function __construct()
    {
    }

    /**
     * "Public" config is the configuration variables that should be made exposed to JavaScript within the mConfig
     * object.
     *
     * @return array
     */
    public static function getPublicConfig()
    {
        return [
            'api_url' => self::$api_url,
            'ajax_limit_default' => self::$ajax_limit_default,
            'base_url' => self::$base_url,
            'rewrite_url' => self::$rewrite_url,
            'schedule_url' => self::$schedule_url,
            'website_url' => self::$website_url,
            'timezone' => self::$timezone,
            'default_module' => self::$default_module,
            'default_action' => self::$default_action,
            'webcam_prefix' => self::$webcam_prefix,
            'short_name' => self::$short_name,
            'long_name' => self::$long_name,
            'founded' => self::$founded,
            'email_domain' => self::$email_domain,
            'facebook' => self::$facebook,
            'audio_upload_max_size' => self::$audio_upload_max_size,
            'payment_url' => self::$yusu_payment_url,
        ];
    }
}
