<?php
require 'Views/MyURY/bootstrap.php';
$twig->setTemplate('MyURY/menu.twig')
        ->addVariable('title', 'Menu')
        ->addVariable('heading', 'Menu')
        ->addVariable('menu', $menu)
        ->addVariable('clickthrough_en', $new_news)
        ->addVariable('clickthrough_msg', $new_news_msg)
        ->render();