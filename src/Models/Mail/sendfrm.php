<?php
/**
 * Form to compose an email to a mailing list
 * @author Lloyd Wallis <lpw@ury.org.uk>
 * @version 20130526
 * @package MyRadio_Mail
 */

$form = (new MyRadioForm('mail_send', 'Mail', 'doSend',
                array(
                    'debug' => true,
                    'title' => 'Send Email'
                )
        ))->addField(
                new MyRadioFormField('subject', MyRadioFormField::TYPE_TEXT,
                        array(
                            'explanation' => '',
                            'label' => '',
                            'options' => array('placeholder' => 'Subject (['.Config::$short_name.'] is prefixed automatically)')
                        )
                )
        )->addField(
                new MyRadioFormField('body', MyRadioFormField::TYPE_BLOCKTEXT,
                        array(
                            'explanation' => '',
                            'label' => ''
                        )
                )
        )->addField(
                new MyRadioFormField('list', MyRadioFormField::TYPE_HIDDEN)
        );