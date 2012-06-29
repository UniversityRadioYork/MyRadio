<?php

$form = new MyURYForm('sched_allocate', $module, 'doAllocate',
                array(
                    'debug' => true,
                    'title' => 'Allocate Show'
                //'template' => 'MyURY/Scheduler/allocate.twig'
        ));

//Set up the terms select box
$terms = Scheduler::getTerms();
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
                new MyURYFormField('Schedule for Term', MyURYFormField::TYPE_SELECT,
                        array(
                            'options' => $term_options,
                            'explanation' => 'Please select what term you are scheduling for'
                            )
                        )
        )
        ->addField(
                new MyURYFormField('Weeks', MyURYFormField::TYPE_CHECKGRP,
                        array('options' =>
                            array(
                                new MyURYFormField('wk1', MyURYFormField::TYPE_CHECK, array('label' => 'Week 1', 'required' => false)),
                                new MyURYFormField('wk2', MyURYFormField::TYPE_CHECK, array('label' => 'Week 2', 'required' => false)),
                                new MyURYFormField('wk3', MyURYFormField::TYPE_CHECK, array('label' => 'Week 3', 'required' => false)),
                                new MyURYFormField('wk4', MyURYFormField::TYPE_CHECK, array('label' => 'Week 4', 'required' => false)),
                                new MyURYFormField('wk5', MyURYFormField::TYPE_CHECK, array('label' => 'Week 5', 'required' => false)),
                                new MyURYFormField('wk6', MyURYFormField::TYPE_CHECK, array('label' => 'Week 6', 'required' => false)),
                                new MyURYFormField('wk7', MyURYFormField::TYPE_CHECK, array('label' => 'Week 7', 'required' => false)),
                                new MyURYFormField('wk8', MyURYFormField::TYPE_CHECK, array('label' => 'Week 8', 'required' => false)),
                                new MyURYFormField('wk9', MyURYFormField::TYPE_CHECK, array('label' => 'Week 9', 'required' => false)),
                                new MyURYFormField('wk10', MyURYFormField::TYPE_CHECK, array('label' => 'Week 10', 'required' => false))
                            ),
                            'explanation' => 'Select what weeks this term this show will be on air'
                        )
                )
);