<?php
use \MyRadio\Config;

Config::$db_hostname = 'postgres';
Config::$db_name = 'myradio';
Config::$db_user = 'myradio';
Config::$db_pass = 'myradio';
Config::$lastfm_api_key = '';
Config::$short_name = 'URN';
Config::$long_name = 'University Radio Nork';
Config::$welcome_email = '   This is a welcome email. You can use #NAME to get the user\'s first name,
   and include #USER and #PASS to tell them their newly created login details.
"';
Config::$base_url = 'https://localhost:4443/myradio/';
Config::$cache_enable = true;
Config::$cache_memcached_servers = [['memcached', 11211]];

// Don't go into setup
Config::$setup = false;
