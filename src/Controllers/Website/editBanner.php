<?php
/**
 * Edit a Banner
 * 
 * @author Lloyd Wallis <lpw@ury.org.uk>
 * @version 20130806
 * @package MyURY_Website
 */

if (!isset($_REQUEST['bannerid'])) {
  throw new MyURYException('You must provide a bannerid', 400);
}

MyURY_Banner::getInstance($_REQUEST['bannerid'])->getEditForm()->render();