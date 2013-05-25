<?php
/**
 * 
 * @todo Proper Documentation
 * @author Lloyd Wallis <lpw@ury.org.uk>
 * @version 20130525
 * @package MyURY_Core
 */
$twig = CoreUtils::getTemplateObject();
$twig->addVariable('serviceName', 'MyURY')
     ->addVariable('submenu', (new MyURYMenu())->getSubMenuForUser(CoreUtils::getModuleID($module), User::getInstance()))
     ->setTemplate('stripe.twig')
     ->addVariable('title', $module)
     ->addVariable('uri', $_SERVER['REQUEST_URI']);

$cuser = User::getInstance();
if ($cuser->hasAuth(AUTH_SELECTSERVICEVERSION)) {
  $twig->addVariable('version_header',
          '<li><a href="?select_version='.CoreUtils::$service_id.'" title="Click to change version">'.
          CoreUtils::getServiceVersionForUser($cuser)['version'].'</a></li>');
} else {
  $twig->addVariable('version_header','');
}

if(User::getInstance()->hasAuth(AUTH_SHOWERRORS)) {
  $twig->addVariable('phperrors', MyURYError::$php_errorlist);
}
if (isset($_REQUEST['message'])) {
  $twig->addInfo(base64_decode($_REQUEST['message']));
}