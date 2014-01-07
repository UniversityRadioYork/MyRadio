<?php
/**
 * In which the form for editing chart releases is created.
 *
 * @version 20130427
 * @author Matt Windsor <matt.windsor@ury.org.uk>
 * @package MyRadio_Charts
 */

$types = MyRadio_ChartType::getAll();
$type_select = [];
foreach($types as $type) {
  $type_select[] = [
    'value' => $type->getID(),
    'text' => $type->getDescription()
  ];
}

$form = (
  new MyRadioForm(
    'charts_editchartrelease',
    $module,
    'doEditChartRelease',
    [
      'title' => 'Edit Chart Release'
    ]
  )
)->addField(
  new MyRadioFormField(
    'chart_type_id',
    MyRadioFormField::TYPE_SELECT,
    [
      'label' => 'Chart Type',
      'explanation' =>
         'The type of chart.',
      'options' => array_merge(
        [['text' => 'Please select...', 'disabled' => true]],
        $type_select
      )
    ]
  )
)->addField(
  new MyRadioFormField(
    'submitted_time',
    MyRadioFormField::TYPE_DATE,
    [
      'label' => 'Release Date',
      'explanation' =>
         'The date the chart is released on.'
    ]
  )
)->addField(
  new MyRadioFormField(
    'zillyhoo',
    MyRadioFormField::TYPE_SECTION,
    [
      'label' => 'Tracks'
    ]
  )
);

// Temporary hack until tabular stuff appears.
for ($i = 1; $i <= 10; $i++) {
  $form->addField(
    new MyRadioFormField(
      'track' . $i,
      MyRadioFormField::TYPE_TRACK,
      [
        'label' => $i,
        'options' => [
          'autotrackname' => true
        ]
      ]
    )
  );
}

$form->addField(new MyRadioFormField('zillyhoo_close', MyRadioFormField::TYPE_SECTION_CLOSE));

$form->addField(
  new MyRadioFormField(
    'chart_release_id',
    MyRadioFormField::TYPE_HIDDEN
  )
);
?>
