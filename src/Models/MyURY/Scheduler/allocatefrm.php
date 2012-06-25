<?php
$form = new MyURYForm('sched_allocate', 'Scheduler', 'doAllocate',
        array(
            'debug' => true,
            //'template' => 'MyURY/Scheduler/allocate.twig'
        ));

$form->addField(
        new MyURYFormField('Text', MyURYFormField::TYPE_TEXT)
        )
        ->addField(
                new MyURYFormField('Number', MyURYFormField::TYPE_NUMBER)
                )
        ->addField(
                new MyURYFormField('Email', MyURYFormField::TYPE_EMAIL)
                )
        ->addField(
                new MyURYFormField('Date', MyURYFormField::TYPE_DATE)
                )
        ->addField(
                new MyURYFormField('Date/Time', MyURYFormField::TYPE_DATETIME)
                )
        ->addField(
                new MyURYFormField('Member', MyURYFormField::TYPE_MEMBER)
                )
        ->addField(
                new MyURYFormField('Track', MyURYFormField::TYPE_TRACK)
                )
        ->addField(
                new MyURYFormField('Artist', MyURYFormField::TYPE_ARTIST)
                )
        ->addField(
                new MyURYFormField('Hidden', MyURYFormField::TYPE_HIDDEN)
                )
        ->addField(
                new MyURYFormField('Select', MyURYFormField::TYPE_SELECT,
                        array('options'=>array(array('value'=>0,'text'=>'test'))))
                )
        ->addField(
                new MyURYFormField('Radio', MyURYFormField::TYPE_RADIO)
                )
        ->addField(
                new MyURYFormField('Checkbox', MyURYFormField::TYPE_CHECK)
                )
        ->addField(
                new MyURYFormField('Day', MyURYFormField::TYPE_DAY)
                )
        ->addField(
                new MyURYFormField('Block Text', MyURYFormField::TYPE_BLOCKTEXT)
                )
        ->addField(
                new MyURYFormField('Time', MyURYFormField::TYPE_TIME)
                );