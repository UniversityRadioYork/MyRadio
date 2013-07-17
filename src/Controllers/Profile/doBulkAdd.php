<?php
/**
 * Allows creation of new URY members!
 * 
 * @author Lloyd Wallis <lpw@ury.org.uk>
 * @version 20130717
 * @package MyURY_Profile
 */
$data = User::getBulkAddForm()->readValues();
$template = CoreUtils::getTemplateObject();

for ($i = 0; $i < sizeof($data['bulkaddrepeater']['fname']); $i++) {
  $params = array();
  foreach ($data['bulkaddrepeater'] as $key => $v) {
    $params[$key] = $data['bulkaddrepeater'][$key][$i];
  }
  $user = User::create($params);
  $template->addInfo('Added Member with ID '.$user->getID());
}

$template->setTemplate('MyURY/text.twig')->render();