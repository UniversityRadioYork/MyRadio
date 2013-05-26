<?php
/**
 * Form to compose an email to a mailing list
 * @author Lloyd Wallis <lpw@ury.org.uk>
 * @version 20130526
 * @package MyURY_Mail
 */

$form = (new MyURYForm('mail_send', 'Mail', 'doSend',
                array(
                    'debug' => true,
                    'title' => 'Send Email'
                )
        ))->addField(
                new MyURYFormField('subject', MyURYFormField::TYPE_TEXT,
                        array(
                            'explanation' => 'Subject of the email',
                            'label' => 'Subject'
                        )
                )
        )->addField(
                new MyURYFormField('body', MyURYFormField::TYPE_BLOCKTEXT,
                        array(
                            'explanation' => '',
                            'label' => ''
                        )
                )
        )->addField(
                new MyURYFormField('list', MyURYFormField::TYPE_HIDDEN)
        );