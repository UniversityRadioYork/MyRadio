<?php

use MyRadio\MyRadio\CoreUtils;
use MyRadio\ServiceAPI\MyRadio_Highlight;

$highlights = MyRadio_Highlight::getLastHighlightsForCurrentUser(25);

$rows = [];
foreach ($highlights as $hl) {
    $row = $hl->toDataSource();
    $row['loggerlink'] = [
        'display' => 'text',
        'value' => 'Audio Logger',
        'title' => 'Download this clip',
        'url' => $clip->getPublicURL(),
    ];
    $clip = $hl->getAutoVizClip();
    if ($clip === null) {
        $row['autovizlink'] = 'No video clip available';
    } else {
        $row['autovizlink'] = [
            'display' => 'text',
            'value' => 'Video Clip',
            'title' => 'Download this clip',
            'url' => $clip->getPublicURL(),
        ];
    }
    $rows[] = $row;
}

CoreUtils::getTemplateObject()->setTemplate('table.twig')
    ->addVariable('tablescript', 'myradio.scheduler.highlights')
    ->addVariable('title', 'Your Highlights')
    ->addVariable('tabledata', $rows)
    ->render();
