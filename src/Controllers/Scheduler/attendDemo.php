<?php
/**
 * Allows URY Trainers to create demo slots for new members to attend
 *
 * @author Lloyd Wallis <lpw@ury.org.uk>
 * @version 24102012
 * @package MyRadio_Scheduler
 */

$result = MyRadio_Demo::attend($_REQUEST['demoid']);
CoreUtils::redirect($module, 'listDemos', ['msg'=>$result]);
