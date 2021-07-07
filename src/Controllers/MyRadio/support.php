<?php

use \MyRadio\MyRadio\CoreUtils;

$tabledata = [
    [
        [
            'display' => 'text',
            'value' => "Advice for Stress (Uni)",
            'url' => "https://www.york.ac.uk/students/health/advice/stress/"
        ],
        "Advice from the University for dealing with stress."
    ],
    [
        [
            'display' => 'text',
            'value' => "Advice for Stress (NHS)",
            'url' => "https://www.nhs.uk/mental-health/feelings-symptoms-behaviours/feelings-and-symptoms/stress/"
        ],
        "Advice from the NHS for dealing with stress."
    ],
    [
        [
            'display' => 'text',
            'value' => "Mental Health Helplines",
            'url' => "https://www.nhs.uk/mental-health/nhs-voluntary-charity-services/charity-and-voluntary-services/get-help-from-mental-health-helplines/"
        ],
        "Helplines on the NHS website for help with mental health."
    ],
    [
        [
            'display' => 'text',
            'value' => "York Open Door Team",
            'url' => "https://www.york.ac.uk/students/health/help/open-door/#d.en.694672"
        ],
        "Someone from the Uni just to talk to."
    ],
    [
        [
            'display' => 'text',
            'value' => "York Nightline",
            'url' => "https://www.yorknightline.org.uk/"
        ],
        "York Nightline for someone just to talk to."
    ],
    [
        [
            'display' => 'text',
            'value' => "The Samaritans",
            'url' => "https://www.samaritans.org/"
        ],
        "Talk to someone from the Samaritans"
    ]

];

CoreUtils::getTemplateObject()->setTemplate('table.twig')
    ->addVariable('title', 'Support Links')
    ->addVariable('tabledata', $tabledata)
    ->addVariable('tablescript', 'myradio.support')
    ->render();
