<?php

/**
 *
 * @todo Proper Documentation
 * @author Lloyd Wallis <lpw@ury.org.uk>
 * @version 02012013
 * @package MyRadio_Scheduler
 */
$form = (new MyRadioForm('sched_reject', $module, 'doReject',
                array(
                    'debug' => false,
                    'title' => 'Reject Season Application'
                )
        ))->addField(
                new MyRadioFormField('season_id', MyRadioFormField::TYPE_HIDDEN)
        )->addField(
                new MyRadioFormField('reason', MyRadioFormField::TYPE_BLOCKTEXT,
                        array(
                            'label' => 'Reason for Rejection: ',
                            'explanation' => 'You can enter a reason here for the application being rejected.'
                            .' If you then choose to send this response to the applicant, they can then edit their'
                            .' application and resubmit.'
                ))
        )->addField(
        new MyRadioFormField('notify_user', MyRadioFormField::TYPE_CHECK,
                array(
                    'label' => 'Notify the Applicant via Email?',
                    'options' => array('checked' => true),
                    'required' => false
        ))
);