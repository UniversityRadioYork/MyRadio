<?php
/**
 * Landing page for Website Tools
 * 
 * @author Lloyd Wallis <lpw@ury.org.uk>
 * @version 20130806
 * @package MyURY_Website
 */

CoreUtils::getTemplateObject()->setTemplate('MyURY/text.twig')->addVariable('title', 'Website Tools')
        ->addVariable('text', 'This section of MyURY lets you control some aspects of the Website, such as banners and themes.')
        ->render();