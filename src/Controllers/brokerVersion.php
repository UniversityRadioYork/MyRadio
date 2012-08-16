<?php
/**
 * This file lets administrators choose a version of the service to use.
 * 
 * @author Lloyd Wallis <lpw@ury.org.uk>
 * @version 15082012
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
if (isset($_POST['svc_version'])) {
  $service = $_POST['svc_name'];
  foreach ($versions as $version) {
    if ($version['version'] === $_POST['svc_version']) {
      $_SESSION['myury_svc_version_'.$service] = $version['version'];
      $_SESSION['myury_svc_version_'.$service.'_path'] = $version['path'];
    }
  }
}

// If the session already has a saved service version, use that
if (isset($_SESSION['myury_svc_version_'.$service])) {
  $service_version = $_SESSION['myury_svc_version_'.$service];
  $path = $_SESSION['myury_svc_version_'.$service.'_path'];
} else {
  // Make a version select form
  require 'Views/MyURY/Core/brokerVersion.php';
  exit;
}
unset($form, $submitted_data, $versions, $svc);