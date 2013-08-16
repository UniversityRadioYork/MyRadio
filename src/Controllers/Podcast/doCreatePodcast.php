<?php
/**
 * Render form to create a new Podcast
 * @author Lloyd Wallis <lpw@ury.org.uk>
 * @version 20130815
 * @package MyURY_Podcast
 */

$data = MyURY_Podcast::getCreateForm()->readValues();

var_dump($data);

