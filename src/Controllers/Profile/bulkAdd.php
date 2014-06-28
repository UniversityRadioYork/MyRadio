<?php
/**
 * Allows creation of new URY members!
 *
 * @author Lloyd Wallis <lpw@ury.org.uk>
 * @version 20130717
 * @package MyRadio_Profile
 */

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    //Submitted
    $data = MyRadio_User::getBulkAddForm()->readValues();
    $template = CoreUtils::getTemplateObject();

    for ($i = 0; $i < sizeof($data['bulkaddrepeater']['fname']); $i++) {
        $params = [];
        foreach ($data['bulkaddrepeater'] as $key => $v) {
            $params[$key] = $data['bulkaddrepeater'][$key][$i];
        }
        try {
            $user = MyRadio_User::create(
                $params['fname'],
                $params['sname'],
                $params['eduroam'],
                $params['sex'],
                $params['collegeid']
            );
            $template->addInfo('Added Member with ID '.$user->getID());
        } catch (MyRadioException $e) {
            $template->addError('Could not add '.$params['eduroam'].': '.$e->getMessage());
        }
    }

    $template->setTemplate('MyRadio/text.twig')->render();

} else {
    //Not Submitted
    MyRadio_User::getBulkAddForm()->render();
}
