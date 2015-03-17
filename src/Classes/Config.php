<?php
/**
 * This file provides the Config class for MyRadio
 * @package MyRadio_Core
 */

namespace MyRadio;

/**
 * Stores configuration settings
 *
 * @package MyRadio_Core
 */
final class Config
{
    /**
     * If true, MyRadio will open the setup wizard when accessed.
     * This should be false in production unless you want bad things to happen.
     * @var boolean
     */
    public $setup = false;
    /**
     * The ID of the "MyRadio" Service. This *should* be the only Service, and therefore never change.
     * It's technically a remnant of the originally slightly overengineered modularisation structure.
     * @var int
     */
    public $service_id = 1;
    /**
     * The Module to assume if one is not explicitly provided. This is usually the MyRadio module, but if you're feeling
     * special, you can make Stats or Library the default...
     * @var String
     */
    public $default_module = 'MyRadio';
    /**
     * The Action to assume if one is not explicitly provided. This is usually default, but if you're feeling special,
     * you could make it index or home, but it'd break a lot of stuff.
     * @var String
     */
    public $default_action = 'default';
    /**
     * The hostname of the PostgreSQL database server
     * @var String
     */
    public $db_hostname    = 'localhost';
    public $db_name        = 'membership';
    /**
     * The username to use connecting to the PostgreSQL database server
     * @var String
     */
    public $db_user        = 'root';
    /**
     * The password to use connecting to the PostgreSQL database server
     * @var String
     */
    public $db_pass        = 'password';
    /**
     * The public timezone of the installation
     * @todo Full review and ensure UTC is used where appropriate (i.e. Scheduler)
     * @var String
     */
    public $timezone       = 'Europe/London';

    /**
     * The base URL of the MyRadio installation
     * @var String
     */
    public $base_url       = '//ury.org.uk/myury/';

    /**
     * The base URL of the schedule - has some JS resources from MyRadio
     * @var String
     */
    public $schedule_url   = '//ury.org.uk/schedule';

    /**
     * Whether nice URL rewrites are enabled
     * If true, then urls will be myury/[module]/[action]
     * If false, then urls will be myury/?module=[module]&action=[action]
     * @var boolean
     */
    public $rewrite_url    = false;

    /**
     * Whether to enable the Caching system
     * Development value: false
     * Production value: true
     * @var boolean
     */
    public $cache_enable   = true;
    /**
     * The name of the class that will provide the MyRadio Caching mechanism.
     * Must be the name of a valid class located in classes/[classname].php and implements CacheProvider
     * @var String
     */
    public $cache_provider = '\MyRadio\APCProvider';
    /**
     * If using the Memcached CacheProvider, set a list of servers to use here.
     * e.g. [['localhost', 11211]] (optionally, a third option, a weighting)
     */
    public static $cache_memcached_servers = [];
    /**
     * How long ServiceAPI items should be cached for by default. Turn this down if you
     * get a lot of edits from other sources.
     * @var int
     */
    public $cache_default_timeout = 86400;

    /**
     * Whether MyRadio errors should be displayed in the browser. If this is set to false, users with the
     * AUTH_SHOWERRORS permission will still see errors.
     * Development value: true
     * Production value: false
     * @var boolean
     */
    public $display_errors = false;

    /**
     * Whether MyRadio Exceptions should be emailed to Computing
     * @var boolean
     */
    public $email_exceptions = true;

    /**
     * Whether template debugging should be enabled
     * Development value: true
     * Production value: false
     * @var boolean
     */
    public $template_debug = true;

    /**
     * The default number of results from an AJAX Search Query
     * This can be overriden on a per-request basis
     * @var int
     */
    public $ajax_limit_default = 25;

    /**
     * The photoid to use for a Joined URY Timeline Event
     * @var int
     */
    public $photo_joined = 1;
    /**
     * The photoid to use for a Gained Officership URY Timeline Event
     * @var int
     */
    public $photo_officership_get = 2;
    /**
     * The photoid to use for a Lost Officership URY Timeline Event
     * @var int
     */
    public $photo_officership_down = 3;
    /**
     * The photoid to use for a Got Show URY Timeline Event
     * @var int
     */
    public $photo_show_get = 4;
    /**
     * The photoid to use for a Got Award URY Timeline Event
     * @var int
     */
    public $photo_award_get = 5;

    /**
     * The id of the news feed to use for news events
     * @var int
     */
    public $news_feed = 1;

    /**
     * The id of the news feed to use for presenter infomation
     * @var int
     */
    public $presenterinfo_feed = 4;

    /**
     * The path to the motion Webcam logs. This must be a file path, but may be NFS/Samba mounter
     * @var String
     */
    public $webcam_archive_path = '/home/motion/videos';

    /**
     * The url of the webcam server status
     * @var String
     */
    public $webcam_current_url;

    /**
     * The url of the webcam server setter
     * @var String
     */
    public $webcam_set_url;

    /**
     * The path to store the original, unencoded copies of MyRadio Podcasts.
     * The originals are archived here for future reencoding.
     * @var String
     */
    public $podcast_archive_path = '/music/podcasts';

    /**
     * The file system path where media should be stored. Used for podcasts, banners and images.
     * @var String
     */
    public $public_media_path = '/home/django/virtualenvs/urysite/assets/media';
    /**
     * This is the HTTP-accessible version of the above directory. Should be absolute or relative to domain, but not
     * protocol-specific, e.g. /media or //ury.org.uk/media
     * @var String
     */
    public $public_media_uri = '/media';

    /**
     * The full web address to the image that will be served for a show if there
     * is not a photo for that show.
     * @var String
     */
    public $default_show_uri = '/media/image_meta/ShowImageMetadata/22.png';

    /**
     * The full web address to the image that will be served on a member's profile page if they do not have a profile
     * photo. The original value, /static/img/default_show_player.png is the main website's placeholder for shows
     * @var String
     */
    public $default_person_uri = '/static/img/default_show_player.png';

    /**
     * The full web address of the image that will be shown for a vacant officer position
     * @var String
     */
    public $vacant_officer_uri = '/media/image_meta/MyRadioImageMetadata/32.jpeg';

    /**
     * The full web address of a copy of the Presenters Contract.
     * @note If this variable is empty, then contracts are disabled.
     * @var String
     */
    public $contract_uri = '';

    /**
     * The file system path to the Central Database. Must be absolute. Can not be smb://, but may be a network share
     * mounted to the file system mountpoint.
     * @var String
     */
    public $music_central_db_path = '/music';

    /**
     * The file to be played if the obit procedure is triggered.
     * This is only used if you use the MyRadio iTones Liquidsoap playout tools
     * @var String
     */
    public $jukebox_obit_file = '/jukebox/OBIT.mp3';

    /**
     * The Samba File Share path to the Central Database.
     * This is used for BAPS compatibility features.
     *
     * If you are not URY, you will likely not need this setting.
     * However, if you have a studio audio playout tool that needs
     * samba paths to files stored in a database, set this here.
     * This data will then be stored in the public.baps_ table.
     *
     * @var String
     */
    public $music_smb_path = '\\\\musicstore.ury.york.ac.uk';

    /**
     * A path to temporarily store uploaded audio files. Recommend somewhere in /tmp,
     * MyRadio needs full r/w access to it to enable Library management.
     * @var String
     */
    public $audio_upload_tmp_dir = '/tmp/myradioaudiouploadcache';

    /**
     * The maximum allowed size of a single Track upload, in MB.
     * Still bound by php.ini settings.
     * @var String
     */
    public $audio_upload_max_size = 15;

    /**
     * The API key to access last.fm's resources.
     *
     * You will need one of these to enable Library management.
     *
     * @var String
     */
    public $lastfm_api_key;

    /**
     * The API Secret to write last.fm's resources.
     * @var String
     */
    public $lastfm_api_secret;

    /**
     * The last.fm group specifically for the University of York. If using
     * this aspect of the code you probably want to change this bit.
     */
    public $lastfm_group = 'University+of+York';

    /**
     * The last.fm nation of choice, at least for us. Again, you might wish to
     * change this bit.
     */
     public $lastfm_geo = 'United+Kingdom';

    /**
     * The array of different versions of tracks one can expect to find in the
     * Central Database. Used for file servers and other systems to ensure
     * the file requested seems legit.
     * @var Array[String]
     */
    public $music_central_db_exts = ['mp3', 'ogg', 'mp3.orig'];

    /**
     * Mailing list to send reporting info to
     * @var String
     * @todo Make this support non-MyRadio managed addresses
     */
    public $reporting_list = 'alerts.myradio';

    /**
     * The IP/hostname of the iTones Liquidsoap Telnet Service
     * @var String
     */
    public $itones_telnet_host = '144.32.64.167';
    /**
     * The port of the iTones Liquidsoap Telnet Service
     * @var int
     */
    public $itones_telnet_port = 1234;

    /**
     * The maximum number of requests in one $itones_request_period per user.
     * @var int
     */
    public $itones_request_maximum = 5;

    /**
     * The period in which a user can use up to $itones_request_maximum requests.
     *
     * This is evaluated as a PostgreSQL INTERVAL: examples of valid values are
     * '1 hour', '5 minutes' or '10:00:00'.
     *
     * @var String
     */
    public $itones_request_period = '1 hour';

    /**
     * The IP/hostname of the Studio Selector Telnet Service
     * @var String
     */
    public $selector_telnet_host = '144.32.64.167';

    /**
     * The port of the Studio Selector Telnet Service
     * @var int
     */
    public $selector_telnet_port = 1354;

    /**
     * The path to the file that reports the state of the remote OB feeds
     * @var String
     */
    public $ob_remote_status_file = '/music/ob_state.conf';

    /**** ERROR REPORTING ****/

    /**
     * The file to store MyRadio Error Logs
     * @var String
     */
    public $log_file = '/var/log/myradio/errors.log';
    /**
     * A lock file on the MyRadio Error Logs. Prevents email spam.
     * @var String
     */
    public $log_file_lock = '/tmp/myradio_errors.lock';
    /**
     * The email to send error reports to. This is different from reporting_list,
     * which does statistical reports, if enabled.
     * @var String
     */
    public $error_report_email = 'alerts.myradio';

    /**
     * The number of seconds an iTones Playlist lock is valid for before it expires.
     * @var int
     */
    public $playlist_lock_time = 30;

    /**
     * The User that MyRadio assumes when doing things as a background task
     * @var int Mr Website
     */
    public $system_user = 779;

    /**
     * This key enables automated access to the YUSU CMS information about URY's members
     *
     * This is literally only useful if you are URY.
     */
    public $yusu_api_key;

    /**
     * The default college for new users that do not specify one.
     * 1 is Unknown. (Unless you're URY, in which case it's 10 because legacy)
     */
    public $default_college = 1;

    /**
     * A path to the file system (preferably in /tmp) that the MyRadio Daemon tools can have write access to. It stores
     * state information about the service that should not be permanent but presist after a reload of the service.
     * @var String
     */
    public $daemon_lock_file = '/tmp/myradio_daemon.lock';

    /**
     * The root URL to the API
     *
     * Must be absolute.
     *
     * @var String
     */
    public $api_url = '/api';

    /**
     * A list of domains that will get a valid Access-Control-Allow-Origin.
     *
     * The default, an empty array, allows all access.
     *
     * @todo Is this a sane default?
     *
     * @var array
     */
    public $api_allowed_domains = [];

    /**
     * The URL prefix to URY's webcam
     *
     * Must be absolute. With trailing /
     *
     * @var String
     */
    public $webcam_prefix = '//ury.org.uk/webcam/';

    /**
     * BRA Server
     * @var String
     */
    public $bra_uri = 'ury.org.uk/bra';
    public $bra_user = '';
    public $bra_pass = '';

    /**
     * Relative path to the API. Must have trailing /
     * @var String
     */
    public $api_uri = '/api/';

    /**
     * Recaptcha settings. Used for password resets.
     *
     * http://recaptcha.net
     *
     * @var String
     */
    public $recaptcha_public_key = 'YOUR_API_KEY';
    public $recaptcha_private_key = 'YOUR_PRIVATE_KEY';

    /**
     * Relative path to the SIS plugins.
     * @var String
     */
    public $sis_plugin_folder = 'Models/SIS/plugins';

    /**
     * Relative path to the SIS tabs.
     * @var String
     */
    public $sis_tab_folder = 'Models/SIS/tabs';

    /**
     * Array of tabs and plugins to be used by SIS. They will be loaded in order.
     */
    public $sis_modules = [
        'presenterinfo',
        'messages',
        'schedule',
        'tracklist',
        'links',
        'selector',
        'webcam',
        'obit'
    ];

    /**
     * URL of the news provider
     * @var string
     */
    public $news_provider = "http://www.irn.co.uk/";

    /**
     * Host that the news provider must be accessed from
     * @var string
     */
    public $news_proxy = "wc10.york.ac.uk:8080";

    /**
     * URY's Membership Fee
     * @var float
     */
    public $membership_fee = 7.00;

    /**
     * If enabled, the Members' News feature on the home page is active
     */
    public $members_news_enable = false;

    /**
     * Authentication
     * LDAP requires the ldap plugin (net/php5-ldap)
     * The Authenticators are tried in order when completing user authentication
     * operations.
     */
    public $authenticators = ['\MyRadio\MyRadio\MyRadioDefaultAuthenticator'];
    public $auth_ldap_server = 'ldap://ldap.york.ac.uk';
    public $auth_ldap_root = 'ou=people,ou=csrv,ou=nos,dc=york,dc=ac,dc=uk';
    public $auth_db_user = '';
    public $auth_db_pass = '';
    public $eduroam_domain = 'york.ac.uk';
    public $auth_ldap_friendly_name = 'IT Services';
    public $auth_ldap_reset_url = 'https://idm.york.ac.uk/';

    /**
    * Email configuration
    */
    //All email domains handled by MyRadio
    public $local_email_domains = [];
    //Primary email domains
    public $email_domain = 'ury.org.uk';

    /**
     * If true, users will be bound to a single Authenticator. Users whose
     * authenticator is NULL will be asked to set an Authenticator after login.
     *
     * If it is false, all authenticators will be valid for all users.
     *
     * @var boolean
     */
    public $single_authenticator = false;

    /**
     * If false, MyRadioDefaultAuthenticator will never pass, passwords will not
     * be set for new users, and the Change Password functionality will not be
     * available.
     *
     * @var boolean
     */
    public $enable_local_passwords = true;

    /**
     * The number of days before the start of the academic year when accounts are inactivated
     * The current choice should mean it resets results week.
     */
    public $account_expiry_before = 49;

    /**** DAEMON CONFIGURATION ****/
    public $d_BAPSSync_enabled = false;
    public $d_EmailQueue_enabled = true;
    public $d_Fingerprinter_enabled = false;
    public $d_LabelFinder_enabled = false;
    public $d_MemberSync_enabled = false;
    public $d_Playlists_enabled = true;
    public $d_Podcast_enabled = true;
    public $d_StatsGen_enabled = true;
    public $d_Explicit_enabled = false;

    /**** STRINGS ****/
    public $short_name = 'URY';
    public $long_name = 'University Radio York';
    public $founded = '1967';
    public $facebook = 'https://www.facebook.com/URY1350';
    public $welcome_email = <<<EOT

   This is a welcome email. You can use #NAME to get the user's first name,
   and include #USER and #PASS to tell them their newly created login details.

EOT;

    /**
     * "Public" config is the configuration variables that should be made exposed to JavaScript within the mConfig
     * object.
     * @return Array
     */
    public function getPublicConfig()
    {
        return [
            'api_url' => $this->api_url,
            'ajax_limit_default' => $this->ajax_limit_default,
            'base_url' => $this->base_url,
            'rewrite_url' => $this->rewrite_url,
            'schedule_url' => $this->schedule_url,
            'timezone' => $this->timezone,
            'default_module' => $this->default_module,
            'default_action' => $this->default_action,
            'webcam_prefix' => $this->webcam_prefix,
            'bra_uri' => $this->bra_uri,
            'bra_user' => $this->bra_user,
            'bra_pass' => $this->bra_pass,
            'short_name' => $this->short_name,
            'long_name' => $this->long_name,
            'founded' => $this->founded,
            'facebook' => $this->facebook,
            'audio_upload_max_size' => $this->audio_upload_max_size
        ];
    }
}
