<?php
$form = new MyURYForm('sched_allocate', 'Scheduler', 'doAllocate',
        array(
            'debug' => true,
            //'template' => 'MyURY/Scheduler/allocate.twig'
        ));

$form->addField(
        new MyURYFormField('testattribute0', MyURYFormField::TYPE_TEXT)
        )
        ->addField(
                new MyURYFormField('testattribute1', MyURYFormField::TYPE_NUMBER)
                )
        ->addField(
                new MyURYFormField('testattribute2', MyURYFormField::TYPE_EMAIL)
                )
        ->addField(
                new MyURYFormField('testattribute3', MyURYFormField::TYPE_DATE)
                )
        ->addField(
                new MyURYFormField('testattribute4', MyURYFormField::TYPE_DATETIME)
                )
        ->addField(
                new MyURYFormField('testattribute5', MyURYFormField::TYPE_MEMBER)
                )
        ->addField(
                new MyURYFormField('testattribute6', MyURYFormField::TYPE_TRACK)
                )
        ->addField(
                new MyURYFormField('testattribute7', MyURYFormField::TYPE_ARTIST)
                )
        ->addField(
                new MyURYFormField('testattribute8', MyURYFormField::TYPE_HIDDEN)
                )
        ->addField(
                new MyURYFormField('testattribute9', MyURYFormField::TYPE_SELECT,
                        array('options'=>array('value'=>0,'text'=>'test')))
                );/*
        ->addField(
                new MyURYFormField('testattribute10', MyURYFormField::TYPE_RADIO)
                )
        ->addField(
                new MyURYFormField('testattribute11', MyURYFormField::TYPE_CHECK)
                )
        ->addField(
                new MyURYFormField('testattribute12', MyURYFormField::TYPE_DAY)
                )
        ->addField(
                new MyURYFormField('testattribute13', MyURYFormField::TYPE_BLOCKTEXT)
                );
        
        