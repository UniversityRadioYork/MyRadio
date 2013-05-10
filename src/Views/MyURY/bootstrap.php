<?php
/**
 * 
 * @todo Proper Documentation
 * @author Lloyd Wallis <lpw@ury.org.uk>
 * @version 21072012
 * @package MyURY_Core
 */
$twig = CoreUtils::getTemplateObject();
$twig->addVariable('serviceName', $service)
     ->addVariable('submenu', (new MyURYMenu())->getSubMenuForUser(CoreUtils::getModuleID(1, $module), User::getInstance()))
     ->setTemplate('stripe.twig')
     ->addVariable('title', $module)
     ->addVariable('uri', $_SERVER['REQUEST_URI']);

$cuser = User::getInstance();
if ($cuser->hasAuth(AUTH_SELECTSERVICEVERSION)) {
  $twig->addVariable('version_header',
          '<li><a href="?select_version='.$service.'" title="Click to change version">'.CoreUtils::getServiceVersionForUser(
                  CoreUtils::getServiceId($service), $cuser)['version'].'</a></li>');
} else {
  $twig->addVariable('version_header','');
}

if(User::getInstance()->hasAuth(AUTH_SHOWERRORS)) {
  $twig->addVariable('phperrors', MyURYError::$php_errorlist);
}
if (isset($_REQUEST['message'])) {
  $twig->addInfo(base64_decode($_REQUEST['message']));
}