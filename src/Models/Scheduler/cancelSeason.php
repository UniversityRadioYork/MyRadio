<?php

/**
 *
 * @todo Proper Documentation
 * @author Lloyd Wallis <lpw@ury.org.uk>
 * @version 12112012
 * @package MyURY_Scheduler
 */

$form = (new MyURYForm('sched_cancel', $module, 'doCancelSeason',
                array(
                    'debug' => false,
                    'title' => 'Cancel Season'
                )
        ))->addField(
                new MyURYFormField('show_season_id', MyURYFormField::TYPE_NUMBER,
                        array('label' => 'Season ID to Cancel'))
        );