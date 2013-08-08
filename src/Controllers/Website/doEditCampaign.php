<?php
/**
 * Edit a Campaign
 * 
 * @author Lloyd Wallis <lpw@ury.org.uk>
 * @version 20130808
 * @package MyURY_Website
 */

$data = (new MyURYForm('test', 'Website', 'doEditCampaign'))
        ->addField(new MyURYFormField('test', MyURYFormField::TYPE_WEEKSELECT))
                ->readValues();

var_dump($data);