<?php
/**
 * Edit a Campaign
 * 
 * @author Lloyd Wallis <lpw@ury.org.uk>
 * @version 20130808
 * @package MyURY_Website
 */

$form = (new MyURYForm('test', 'Website', 'doEditCampaign'))
        ->addField(new MyURYFormField('test', MyURYFormField::TYPE_WEEKSELECT));
$data = $form->readValues();

echo "You Selected:<br>";

$days = [
    '',
    'Monday',
    'Tuesday',
    'Wednesday',
    'Thursday',
    'Friday',
    'Saturday',
    'Sunday'
];

foreach ($data['test'] as $value) {
  echo $days[$value['day']].' '.gmdate('H:i', $value['start_time']).'-'.gmdate('H:i', $value['end_time']).'<br>';
}

$form->editMode(0, $data)->render();