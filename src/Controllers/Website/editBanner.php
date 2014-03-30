<?php
/**
 * Edit a Banner
 *
 * @author Lloyd Wallis <lpw@ury.org.uk>
 * @version 20130806
 * @package MyRadio_Website
 */

if (!isset($_REQUEST['bannerid'])) {
  throw new MyRadioException('You must provide a bannerid', 400);
}

$banner = MyRadio_Banner::getInstance($_REQUEST['bannerid']);
$banner->getEditForm()->render(['bannerName' => $banner->getAlt()]);
