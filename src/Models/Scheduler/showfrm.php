<?php

/**
 *
 * @author Lloyd Wallis <lpw@ury.org.uk>
 * @version 20130727
 * @package MyRadio_Scheduler
 */
$form = (
    new MyRadioForm(
        'sched_show',
        'Scheduler',
        'doShow',
        array(
            'debug' => true,
            'title' => 'Edit Show'
        )
    )
)->addField(
    new MyRadioFormField('grp-basics', MyRadioFormField::TYPE_SECTION, array('label' => 'About My Show'))
)->addField(
    new MyRadioFormField(
        'title',
        MyRadioFormField::TYPE_TEXT,
        array(
            'explanation' => 'Enter a name for your new show. Try and make it unique.',
            'label' => 'Show Name'
        )
    )
)->addField(
    new MyRadioFormField(
        'description',
        MyRadioFormField::TYPE_BLOCKTEXT,
        array(
            'explanation' => 'Describe your show as best you can. This goes on the public-facing website.',
            'label' => 'Description'
        )
    )
)->addField(
    new MyRadioFormField(
        'genres',
        MyRadioFormField::TYPE_SELECT,
        array(
            'options' => array_merge(
                array(array('text' => 'Please select...', 'disabled' => true)),
                MyRadio_Scheduler::getGenres()
            ),
            'label' => 'Genre',
            'explanation' => 'What type of music do you play, if any?'
        )
    )
)->addField(
    new MyRadioFormField(
        'tags',
        MyRadioFormField::TYPE_TEXT,
        array(
            'label' => 'Tags',
            'explanation' => 'A set of keywords to describe your show generally, seperated with spaces.'
        )
    )
)->addField(
    new MyRadioFormField('grp-basics_close', MyRadioFormField::TYPE_SECTION_CLOSE)
)->addField(
    new MyRadioFormField('grp-credits', MyRadioFormField::TYPE_SECTION, array('label' => 'Who\'s On My Show'))
)->addField(
    new MyRadioFormField(
        'credits',
        MyRadioFormField::TYPE_TABULARSET,
        array(
            'options' => array(
                new MyRadioFormField(
                    'member',
                    MyRadioFormField::TYPE_MEMBER,
                    array(
                        'explanation' => '',
                        'label' => 'Credit'
                    )
                ),
                new MyRadioFormField(
                    'credittype',
                    MyRadioFormField::TYPE_SELECT,
                    array(
                        'options' => array_merge(
                            array(array('text' => 'Please select...', 'disabled' => true)),
                            MyRadio_Scheduler::getCreditTypes()
                        ),
                        'explanation' => '',
                        'label' => 'Role'
                    )
                )
            )
        )
    )
)->addField(
    new MyRadioFormField('grp-credits_close', MyRadioFormField::TYPE_SECTION_CLOSE)
)->addField(
    new MyRadioFormField(
        'mixclouder',
        MyRadioFormField::TYPE_CHECK
        array(
            'explanation' => 'If ticked, your shows will automatically be uploaded to mixcloud',
            'label' => 'Enable Mixcloud',
            'options' => ['checked' => true],
            'required' => false
        )
    )
);
