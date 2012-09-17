<?php
/**
 * 
 * @todo Proper Documentation
 * @author Lloyd Wallis <lpw@ury.org.uk>
 * @version 21072012
 * @package MyURY_Scheduler
 */
$form = new MyURYForm('sched_allocate', $module, 'doAllocate',
                array(
                    'debug' => true,
                    'title' => 'Allocate Show'
                //'template' => 'MyURY/Scheduler/allocate.twig'
        ));

//Set up the terms select box
$terms = MyURY_Scheduler::getTerms();
$term_options = array();

foreach ($terms as $term) {
  $term_options[] = array(
      'value' => $term['termid'],
      'text' => $term['descr'] . ' ' . date('Y', $term['start'])
  );
}
unset($terms);

//Set up the weeks checkboxes
$weeks = array();
for ($i = 1; $i <=10; $i++) {
  $weeks[] = new MyURYFormField('wk'.$i, MyURYFormField::TYPE_CHECK, array('label' => 'Week '.$i, 'required' => false));
}

$form->addField(
                new MyURYFormField('term', MyURYFormField::TYPE_SELECT,
                        array(
                            'options' => $term_options,
                            'explanation' => 'Please select what term you are scheduling for',
                            'label' => 'Schedule for Term'
                            )
                        )
        )
        ->addField(
                new MyURYFormField('weeks', MyURYFormField::TYPE_CHECKGRP,
                        array('options' => $weeks,
                            'explanation' => 'Select what weeks this term this show will be on air',
                            'label' => 'Schedule for Weeks'
                        )
                )
)->addField(
        new MyURYFormField('presenters', MyURYFormField::TYPE_MEMBER,
                array(
                    'repeating' => true,
                    'explanation' => 'Who\'s on this show?',
                    'label' => 'Presenters'
                    )
                )
        );