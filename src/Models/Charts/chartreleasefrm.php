<?php
/**
 * In which the form for editing chart releases is created.
 *
 * @version 20130427
 * @author Matt Windsor <matt.windsor@ury.org.uk>
 * @package MyURY_Charts
 */

$types = MyURY_ChartType::getAll();
$type_select = [];
foreach($types as $type) {
  $type_select[] = [
    'value' => $type->getID(),
    'text' => $type->getDescription()
  ];
}

$form = (
  new MyURYForm(
    'charts_editchartrelease',
    $module,
    'doEditChartRelease',
    [
      'title' => 'Edit Chart Release'
    ]
  )
)->addField(
  new MyURYFormField(
    'chart_type_id',
    MyURYFormField::TYPE_SELECT,
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
  new MyURYFormField(
    'submitted_time',
    MyURYFormField::TYPE_DATE,
    [
      'label' => 'Release Date',
      'explanation' =>
         'The date the chart is released on.'
    ]
  )
)->addField(
  new MyURYFormField(
    'zillyhoo',
    MyURYFormField::TYPE_SECTION,
    [
      'label' => 'Tracks'
    ]
  )
);

// Temporary hack until tabular stuff appears.
for ($i = 1; $i <= 10; $i++) {
  $form->addField(
    new MyURYFormField(
      'track' . $i,
      MyURYFormField::TYPE_TRACK,
      [
        'label' => $i,
        'options' => [
          'autotrackname' => true
        ]
      ]
    )
  );
}

$form->addField(
  new MyURYFormField(
    'chart_release_id',
    MyURYFormField::TYPE_HIDDEN
  )
);
?>
