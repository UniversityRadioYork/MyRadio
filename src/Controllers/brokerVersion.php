<?php
/**
 * This file lets administrators choose a version of the service to use.
 * 
 * @author Lloyd Wallis <lpw@ury.org.uk>
 * @version 20130509
 * @package MyURY_Core
 * 
 * @uses $service - The current service being requested
 * @uses $member - The current user
 * 
 * Sets the $service_version Global Variable
 */

// Get a list of Service Versions for this Service
$versions = CoreUtils::getServiceVersions(CoreUtils::getServiceID($service));

// If the version selector has just been submitted, update the session
if (isset($_REQUEST['svc_version'])) {
  $serviceid = CoreUtils::getServiceId($_POST['svc_name']);
  foreach ($versions as $version) {
    if ($version['version'] === $_POST['svc_version']) {
      $_SESSION['myury_svc_version_'.$serviceid] = $version['version'];
      $_SESSION['myury_svc_version_'.$serviceid.'_path'] = $version['path'];
    }
  }
  header('Location: ?service='.$_REQUEST['svc_name']);
  exit;
}

if (isset($_REQUEST['select_version'])) {
  $service = $_REQUEST['select_version'];
  $versions = CoreUtils::getServiceVersions(CoreUtils::getServiceId($_REQUEST['select_version']));
  require 'Views/MyURY/Core/brokerVersion.php';
  exit;
}