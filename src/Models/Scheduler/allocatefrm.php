<?php

/**
 * !Important! This form requires pre-populated data to be rendered completely in the form of a $season variable.
 *
 * @todo This should probably be a method in something, what with it taking parameters and all
 * @todo Proper Documentation
 * @author Lloyd Wallis <lpw@ury.org.uk>
 * @version 21072012
 * @package MyRadio_Scheduler
 */
$form = new MyRadioForm('sched_allocate', $module, 'doAllocate',
                array(
                    'title' => 'Allocate Timeslots to Season',
                    'template' => 'Scheduler/allocate.twig',
        ));

//Set up the weeks checkboxes
$weeks = array();
for ($i = 1; $i <= 10; $i++) {
  $weeks[] = new MyRadioFormField('wk' . $i, MyRadioFormField::TYPE_CHECK,
                  array(
                      'label' => 'Week ' . $i,
                      'required' => false,
                      'options' => array('checked' => in_array($i, $season->getRequestedWeeks()))
          ));
}

//Set up the requested times radios
$times = array();
$i = 0;
foreach ($season->getRequestedTimesAvail() as $time) {
  $times[] = array(
      'value' => $i,
      'text' => $time['time'] . ' ' . $time['info'],
      'disabled' => $time['conflict'],
      'class' => $time['conflict'] ? 'ui-state-error' : ''
  );
  $i++;
}

$times[] = array('value' => -1, 'text' => 'Other (Choose below)');

$form->addField(
        new MyRadioFormField('weeks', MyRadioFormField::TYPE_CHECKGRP,
                array('options' => $weeks,
                    'label' => 'Schedule for Weeks'
                )
        )
)->addField(
        new MyRadioFormField('time', MyRadioFormField::TYPE_RADIO,
                array('options' => $times, 'label' => 'Timeslot', 'required' => false)
        )
)->addField(
        new MyRadioFormField('timecustom_day', MyRadioFormField::TYPE_DAY,
                array('label' => 'Other Day: ', 'required' => false))
)->addField(
        new MyRadioFormField('timecustom_stime', MyRadioFormField::TYPE_TIME,
                array('label' => 'from', 'required' => false))
)->addField(
        new MyRadioFormField('timecustom_etime', MyRadioFormField::TYPE_TIME,
                array('label' => 'duration', 'required' => false, 'value' => '01:00'))
);
