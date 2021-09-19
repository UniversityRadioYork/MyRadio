<?php

use MyRadio\MyRadio\CoreUtils;
use MyRadio\MyRadio\URLUtils;
use MyRadio\ServiceAPI\MyRadio_ShortURL;

CoreUtils::getTemplateObject()->setTemplate('Website/shortUrls.twig')
    ->addVariable('title', 'Short URLs')
    ->addVariable('newshorturlurl', URLUtils::makeURL('Website', 'editShortUrl'))
    ->addVariable('tabledata', CoreUtils::dataSourceParser(MyRadio_ShortURL::getAll()))
    ->addVariable('tablescript', 'myradio.website.shorturllist')
    ->render();

