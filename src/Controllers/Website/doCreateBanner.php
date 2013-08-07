<?php
/**
 * Create a Banner
 * 
 * @author Lloyd Wallis <lpw@ury.org.uk>
 * @version 20130806
 * @package MyURY_Website
 */

$data = MyURY_Banner::getBannerForm()->readValues();

var_dump($data);