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
        [
            'debug' => true,
            'title' => 'Edit Show'
        ]
    )
)->addField(
    new MyRadioFormField('grp-basics', MyRadioFormField::TYPE_SECTION, ['label' => 'About My Show'])
)->addField(
    new MyRadioFormField(
        'title',
        MyRadioFormField::TYPE_TEXT,
        [
            'explanation' => 'Enter a name for your new show. Try and make it unique.',
            'label' => 'Show Name'
        ]
    )
)->addField(
    new MyRadioFormField(
        'description',
        MyRadioFormField::TYPE_BLOCKTEXT,
        [
            'explanation' => 'Describe your show as best you can. This goes on the public-facing website.',
            'label' => 'Description'
        ]
    )
)->addField(
    new MyRadioFormField(
        'genres',
        MyRadioFormField::TYPE_SELECT,
        [
            'options' => array_merge(
                [['text' => 'Please select...', 'disabled' => true]],
                MyRadio_Scheduler::getGenres()
            ),
            'label' => 'Genre',
            'explanation' => 'What type of music do you play, if any?'
        ]
    )
)->addField(
    new MyRadioFormField(
        'tags',
        MyRadioFormField::TYPE_TEXT,
        [
            'label' => 'Tags',
            'explanation' => 'A set of keywords to describe your show generally, seperated with spaces.'
        ]
    )
)->addField(
    new MyRadioFormField('grp-basics_close', MyRadioFormField::TYPE_SECTION_CLOSE)
)->addField(
    new MyRadioFormField('grp-credits', MyRadioFormField::TYPE_SECTION, ['label' => 'Who\'s On My Show'])
)->addField(
    new MyRadioFormField(
        'credits',
        MyRadioFormField::TYPE_TABULARSET,
        [
            'options' => [
                new MyRadioFormField(
                    'member',
                    MyRadioFormField::TYPE_MEMBER,
                    [
                        'explanation' => '',
                        'label' => 'Credit'
                    ]
                ),
                new MyRadioFormField(
                    'credittype',
                    MyRadioFormField::TYPE_SELECT,
                    [
                        'options' => array_merge(
                            [['text' => 'Please select...', 'disabled' => true]],
                            MyRadio_Scheduler::getCreditTypes()
                        ),
                        'explanation' => '',
                        'label' => 'Role'
                    ]
                )
            ]
        ]
    )
)->addField(
    new MyRadioFormField('grp-credits_close', MyRadioFormField::TYPE_SECTION_CLOSE)
)->addField(
    new MyRadioFormField(
        'mixclouder',
        MyRadioFormField::TYPE_CHECK,
        [
            'explanation' => 'If ticked, your shows will automatically be uploaded to mixcloud',
            'label' => 'Enable Mixcloud',
            'options' => ['checked' => true],
            'required' => false
        ]
    )
);
