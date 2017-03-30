<?php
/**
 * Checks that necessary modules and resources are available for
 * MyRadio to get started.
 *
 * @todo    Check if rewrites work
 * @todo    Check if static resources load?
 */

/**
 * Helper function for size checks.
 */
function convertPHPSizeToBytes($sSize)
{
    if (is_numeric($sSize)) {
        return $sSize;
    }
    $sSuffix = substr($sSize, -1);
    $iValue = substr($sSize, 0, -1);
    switch (strtoupper($sSuffix)) {
        case 'P':
            $iValue *= 1024;
            //no break
        case 'T':
            $iValue *= 1024;
            //no break
        case 'G':
            $iValue *= 1024;
            //no break
        case 'M':
            $iValue *= 1024;
            //no break
        case 'K':
            $iValue *= 1024;
            break;
    }

    return $iValue;
}

$required_modules = [
    [
        'module' => 'curl',
        'success' => 'cURL can be used to embed the IRN news service into SIS.',
        'fail' => 'If you had the <a href="http://www.php.net/manual/en/book.curl.php">cURL extension</a> '
        .'MyRadio could use it provide IRN news information in SIS.',
        'required' => false,
    ],
    [
        'module' => 'geoip',
        'success' => 'The GeoIP extension can be used to provide location functionality for Stats and SIS modules.',
        'fail' => 'If you had the <a href="http://www.php.net/manual/en/book.geoip.php">GeoIP extension</a> '
        .'MyRadio could provide location information for the Studio Information Service and Statistics.',
        'required' => false,
    ],
    [
        'module' => 'gd',
        'success' => 'The Image (GD) extension can be used to provide upload functionality '
        .'for the Podcast, Profile and Website modules.',
        'fail' => 'If you had the <a href="http://www.php.net/manual/en/book.image.php">Image (GD) extension</a> '
        .'MyRadio could be used to manage image content on Podcasts, Profiles and a frontend website.',
        'required' => false,
    ],
    [
        'module' => 'ldap',
        'success' => 'The LDAP extension can be used to provide external authenticators that use the LDAP protocol.',
        'fail' => 'If you had the <a href="http://www.php.net/manual/en/book.ldap.php">LDAP extension</a> '
        .'MyRadio could integrate with external authentication providers.',
        'required' => false,
    ],
    [
        'module' => 'pgsql',
        'success' => 'You have an appropriate database driver installed.',
        'fail' => 'The <a href="http://www.php.net/manual/en/book.pgsql.php">PostgreSQL extension</a> '
        .'is required for MyRadio to talk to a database. Without this, it can\'t do much.',
        'required' => true,
    ],
    [
        'module' => 'session',
        'success' => 'You have the session extension installed.',
        'fail' => 'The <a href="http://www.php.net/manual/en/book.session.php">Session extension</a> '
        .'is required for MyRadio to talk to keep track of who is logged in.',
        'required' => true,
    ],
];
$required_classes = [
    [
        'class' => '\Twig_Environment',
        'success' => 'You have Twig installed! This is required for MyRadio to generate web pages.',
        'fail' => 'Your server needs to have Twig installed in order to continue. See '
        .'<a href="http://twig.sensiolabs.org/doc/installation.html">the Twig documentation</a> for more information.',
        'required' => true,
    ],
];
$function_checks = [
    [
        //Check that max post size is at least 40MB
        //this still won't be enough for most podcasts, but it should be for MP3s
        'function' => function () {
            return min(
                convertPHPSizeToBytes(ini_get('post_max_size')),
                convertPHPSizeToBytes(ini_get('upload_max_filesize'))
            ) > 40960;
        },
        'success' => 'Your server is configured to support large file uploads.',
        'fail' => 'Your server is set to have a small (<40MB) upload limit. Consider tweaking your php.ini to prevent '
        .'issues using Show Planner, Podcasts and other file upload utilities.',
        'required' => false,
    ],
];

$ready = true;
$problems = [];
$warnings = [];
$successes = [];

if (!defined('PHP_VERSION_ID')) {
    $version = explode('.', PHP_VERSION);

    define('PHP_VERSION_ID', ($version[0] * 10000 + $version[1] * 100 + $version[2]));
}

if (PHP_VERSION_ID < 50400) {
    $ready = false;
    $problems[] = 'You must be running at least PHP 5.4.';
} else {
    $successes[] = 'You are running PHP '.PHP_VERSION.'.';
}

foreach ($required_modules as $module) {
    if (!extension_loaded($module['module'])) {
        if ($module['required']) {
            $ready = false;
            $problems[] = $module['fail'];
        } else {
            $warnings[] = $module['fail'];
        }
        if (isset($module['set_fail'])) {
            $config_overrides[$module['set_fail'][0]] = $module['set_fail'][1];
        }
    } else {
        $successes[] = $module['success'];
    }
}
foreach ($required_classes as $class) {
    if (!class_exists($class['class'])) {
        if ($class['required']) {
            $ready = false;
            $problems[] = $class['fail'];
        } else {
            $warnings[] = $class['fail'];
        }
    } else {
        $successes[] = $class['success'];
    }
}
foreach ($function_checks as $check) {
    if (!$check['function']()) {
        if ($check['required']) {
            $ready = false;
            $problems[] = $check['fail'];
        } else {
            $warnings[] = $check['fail'];
        }
    } else {
        $successes[] = $check['success'];
    }
}

?>
<!DOCTYPE html>
<html>
  <head>
    <title>Welcome to MyRadio</title>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
    <link rel="Stylesheet" type="text/css" href="css/vendor/bootstrap.min.css">
    <link rel="Stylesheet" type="text/css" href="css/vendor/bootstrap-theme.min.css">
    <link rel="Stylesheet" type="text/css" href="css/style.css">

    <script type="text/javascript" src="js/vendor/jquery-2.1.0.min.js"></script>
    <script type="text/javascript" src="js/vendor/bootstrap.min.js"></script>
  </head>
  <body>
    <nav class="navbar navbar-inverse navbar-fixed-top" role="navigation">
      <div class="container">
        <div class="navbar-header">
          <a class="navbar-brand">MyRadio</a>
        </div>
      </div>
    </nav>
    <br>
    <div class="container main-container">
      <h1>Hello there!</h1>
      <p>
        It looks like you're trying to install MyRadio! Would you like some help with that?
        No? Well too bad, I'm not a paperclip you can hide.
      </p>
      <p>I'm just running some background checks to see if you're ready to go.</p>
        <?php
        if ($ready) {
            ?>
            <p class="alert alert-success">Good news! It looks like you're ready to go.
            <a href="?c=dbserver">Click here to continue</a>.</p>
        <?php
        } else {
            ?>
            <p class="alert alert-danger">
              Uh oh! It looks like there's some things you'll have to get sorted out before you can continue.
              Follow the advice below, then <a href=''>refresh this page</a> to try again.
            </p>
        <?php
          echo '<h3>The following tests failed and must be fixed before you can proceed:</h3><ul>';
        foreach ($problems as $problem) {
            echo '<li>'.$problem.'</li>';
        }
            echo '</ul>';
        }

        if (empty($warnings)) {
            if ($ready) {
                echo '<p><span class="glyphicon glyphicon-ok"></span> Amazing! '
                   .'Your server is absolutely <em>perfect</em> for running MyRadio.</p>';
            }
        } else {
            echo '<h3>The following tests failed, but they aren\'t required for MyRadio to run:</h3><ul>';
            foreach ($warnings as $warning) {
                echo '<li>'.$warning.'</li>';
            }
            echo '</ul>';
        }

        if (!empty($successes)) {
            echo '<h3>The following tests passed without any issues:</h3><ul>';
            foreach ($successes as $success) {
                echo '<li>'.$success.'</li>';
            }
            echo '</ul>';
        }

        if ($ready === false or !empty($warnings)) {
            ?>
            <h3>Cheating</h3>
            <p>If you're using Ubuntu (&gt;=16.04), the following commands (as root) will get you most of the way:</p>
            <code>
              apt install php-curl php-geoip php-gd php-ldap php-pgsql php-mbstring php-dev composer<br>
              composer update<br>
              service apache2 restart
            </code>
        <?php
        }
        ?>
    </div>
      <footer class="footer">
        <div class="container">
          <div class="row">
            <div class="col-md-6">
              MyRadio by University Radio York
            </div>
          </div>
        </div>
      </footer>
  </body>
</html>
