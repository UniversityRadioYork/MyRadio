<?php
/**
 * In which the form for editing chart types is created.
 *
 * @version 20130426
 * @author Matt Windsor <matt.windsor@ury.org.uk>
 * @package MyURY_Charts
 */

var_dump($module);
$form = (
  new MyURYForm(
    'charts_editcharttype',
    $module,
    'doEditChartType',
    [
      'title' => 'Edit Chart Type'
    ]
  )
)->addField(
  new MyURYFormField(
    'name',
    MyURYFormField::TYPE_TEXT,
    [
      'label' => 'Identifier',
      'explanation' =>
         'What the chart will be referred to as in the website code.'
    ]
  )
)->addField(
  new MyURYFormField(
    'description',
    MyURYFormField::TYPE_TEXT,
    [
      'label' => 'Name',
      'explanation' =>
         'What the chart will be called on the website itself.'
    ]
  )
)->addField(
  new MyURYFormField(
    'chart_type_id',
    MyURYFormField::TYPE_HIDDEN
  )
);
?>
