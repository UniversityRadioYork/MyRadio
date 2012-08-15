<?php

/**
 *
 * @todo Proper Documentation
 * @author Lloyd Wallis <lpw@ury.org.uk>
 * @version 15082012
 * @package MyURY_Core
 * 
 * @uses $versions from brokerVersion.php
 */

$form = (new MyURYForm('broker_versionsel', $module, $action,
                array(
                    'debug' => true,
                    'title' => 'Choose Service Version'
                    )
        ))->addField(
        new MyURYFormField('version', MyURYFormField::TYPE_SELECT,
                array(
                    'options' => array_merge(array(array('text' => 'Please select...', 'disabled' => true)), $versions),
                    'explanation' => 'What version of this MyURY Service would you like to use today? Use the Live build for standard use, and the others for development depending on your needs.',
                    'label' => $service.' Service Version'
                )
        )
);