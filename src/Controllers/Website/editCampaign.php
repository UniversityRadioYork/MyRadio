<?php
/**
 * Edit a Campaign
 * 
 * @author Lloyd Wallis <lpw@ury.org.uk>
 * @version 20130808
 * @package MyURY_Website
 */

(new MyURYForm('test', 'Website', 'default'))
        ->addField(new MyURYFormField('test', MyURYFormField::TYPE_WEEKSELECT))
                ->render();