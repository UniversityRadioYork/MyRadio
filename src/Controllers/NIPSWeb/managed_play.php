<?php
/**
 * Streams a managed database track
 * 
 * @author Lloyd Wallis <lpw@ury.org.uk>
 * @version 30032013
 * @package MyRadio_NIPSWeb
 */
if (!isset($_REQUEST['managedid'])) {
  throw new MyRadioException('Bad Request - managedid required.', 400);
}
$managedid = (int)$_REQUEST['managedid'];

NIPSWeb_Views::serveMP3(NIPSWeb_ManagedItem::getInstance($managedid)->getPath());