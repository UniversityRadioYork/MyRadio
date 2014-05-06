<?php
/**
 * Sets up the database data for a new installation of MyRadio
 *
 * @version 20140506
 * @author Lloyd Wallis <lpw@ury.org.uk>
 * @package MyRadio_Core
 */

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
	//???
} else {
	CoreUtils::getTemplateObject()
		->setTemplate('Setup/dbdata.twig')
		->addVariable('title', 'Database Data')
		->render();
}