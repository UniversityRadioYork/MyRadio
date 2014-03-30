<?php
/**
 *
 * @todo Proper Documentation
 * @author Lloyd Wallis <lpw@ury.org.uk>
 * @version 21072012
 * @package MyRadio_Core
 */
header('Content-Type: application/json');
header('HTTP/1.1 200 OK');

//Decode to datasource if needed
$data = CoreUtils::dataSourceParser($data);

if (!empty(MyRadioError::$php_errorlist)) {
  $data['myury_errors'] = MyRadioError::$php_errorlist;
}

echo json_encode($data);
