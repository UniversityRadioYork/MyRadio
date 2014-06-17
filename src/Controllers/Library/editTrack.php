<?php
/**
 * Allows URY Librarians  to create edit Tracks
 *
 * @author Lloyd Wallis <lpw@ury.org.uk>
 * @version 20130722
 * @package MyRadio_Library
 */

//The Form definition
$form = (
    new MyRadioForm(
        'lib_edittrack',
        $module,
        $action,
        [
            'title' => 'Edit Track'
        ]
    )
)->addField(new MyRadioFormField('title', MyRadioFormField::TYPE_TEXT, ['label' => 'Title'])
)->addField(new MyRadioFormField('artist', MyRadioFormField::TYPE_TEXT, ['label' => 'Artist'])
)->addField(new MyRadioFormField('album', MyRadioFormField::TYPE_ALBUM, ['label' => 'Album']));


if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    //Submitted
    $data = $form->readValues();

    $track = MyRadio_Track::getInstance($data['id']);
    $track->setTitle($data['title']);
    $track->setArtist($data['artist']);
    $track->setAlbum($data['album']);

    CoreUtils::backWithMessage('Track Updated.');

} else {
    //Not Submitted

    $track = MyRadio_Track::getInstance($_REQUEST['trackid']);

    $form->editMode(
        $track->getID(),
        [
            'title' => $track->getTitle(),
            'artist' => $track->getArtist(),
            'album' => $track->getAlbum()->getID()
        ]
    )->render();
}
