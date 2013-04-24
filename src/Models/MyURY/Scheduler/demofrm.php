<?php

/**
 *
 * @todo Proper Documentation
 * @author Lloyd Wallis <lpw@ury.org.uk>
 * @version 21072012
 * @package MyURY_Scheduler
 */

$form = (new MyURYForm('sched_demo', $module, 'doDemo',
                array(
                    'title' => 'Create Demo'
                )
        ))->addField(new MyURYFormField('demo-datetime', MyURYFormField::TYPE_DATETIME, array('label' => 'Date and Time of the Demo'))
);