<?php
CoreUtils::getTemplateObject()->setTemplate('table.twig')
    ->addVariable('tablescript', 'myury.scheduler.timeslotlist')
    ->addVariable('title', 'Episodes of '.$season->getMeta('title'))
    ->addVariable('tabledata', ServiceAPI::setToDataSource($timeslots))
    ->render();
