<?php
/**
 * In which the form for editing chart types is created.
 *
 * @version 20130426
 * @author Matt Windsor <matt.windsor@ury.org.uk>
 * @package MyRadio_Charts
 */

var_dump($module);
$form = (
  new MyRadioForm(
    'charts_editcharttype',
    $module,
    'doEditChartType',
    [
      'title' => 'Edit Chart Type'
    ]
  )
)->addField(
  new MyRadioFormField(
    'name',
    MyRadioFormField::TYPE_TEXT,
    [
      'label' => 'Identifier',
      'explanation' =>
         'What the chart will be referred to as in the website code.'
    ]
  )
)->addField(
  new MyRadioFormField(
    'description',
    MyRadioFormField::TYPE_TEXT,
    [
      'label' => 'Name',
      'explanation' =>
         'What the chart will be called on the website itself.'
    ]
  )
)->addField(
  new MyRadioFormField(
    'chart_type_id',
    MyRadioFormField::TYPE_HIDDEN
  )
);
?>
