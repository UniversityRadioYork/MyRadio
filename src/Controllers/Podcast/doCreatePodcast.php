<?php
/**
 * Render form to create a new Podcast
 * @author Lloyd Wallis <lpw@ury.org.uk>
 * @version 20130815
 * @package MyRadio_Podcast
 */

$data = MyRadio_Podcast::getCreateForm()->readValues();

MyRadio_Podcast::create($data['title'],
        $data['description'],
        explode(' ', $data['tags']),
        $data['file']['tmp_name'],
        empty($data['show']) ? null: MyRadio_Show::getInstance($data['show']),
        $data['credits']);

header('Location: '.CoreUtils::makeURL('Podcast', 'default'));
