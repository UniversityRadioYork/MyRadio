<?php

$values = MyRadio_JsonFormLoader::loadFromModule(
    $module, 'setCover', 'doSetCover'
)->readValues();

if (!isset($values['podcastid'])) {
    throw new MyRadioException('Podcast ID was not provided.', 400);
}

$podcast = MyRadio_Podcast::getInstance($values['podcastid']);

if (!currentUserCanEditPodcast($podcast)) {
    CoreUtils::requirePermission(AUTH_PODCASTANYSHOW);
}

switch($values['cover_method']) {
case 'existing':
    existingCoverFile($values);
    break;
case 'new':
    uploadNewCoverFile($values);
    break;
default:
    throw new MyRadioException('Unknown cover upload method.', 400);
}


//
// Helper functions
//

function existingCoverFile($values) {

    setCoverMetadata($values['podcastid'], $url);
}

function setCoverMetadata($podcastid, $url) {
    if (empty($url)) {
        throw new MyRadioException('URL is blank.');
    }

    self::$db->query('
        INSERT INTO
            uryplayer.podcast_image_metadata(
                metadata_key_id, podcast_id, memberid, approvedid,
                metadata_value, effective_from, effective_to
            )
        VALUES
            (10, $1, $2, $2, $3, NOW(), NULL),
            (11, $1, $2, $2, $3, NOW(), NULL)
        ',
        [
            $podcastid,
            MyRadio_User::getInstance(),
            $url
        ]
    );
}

function uploadNewCoverFile($values) {
    $temporary = $values['new_cover']['tmp_file'];
    if (empty($temporary)) {
        throw new MyRadioException('No new cover file uploaded.', 400);
    }

    $url = moveCoverFile($values['podcastid'], $temporary);

    setCoverMetadata($values['podcastid'], $url);
}

function moveCoverFile($podcastid, $temporary_file) {
    $new_path = makeCoverFilePath($temporary_file);
    checkCoverFileUnique($new_path);
    return moveCoverFileTo($new_path, $temporary_file);
}

function makeCoverFilePath($podcastid, $temporary_file) {
    return (
        coverFileDirectory() .
        'podcast' .
        $podcastid .
        '-' .
        time() .
        coverFileFormat()
    );
}

function coverFileDirectory() {
    return Config::$public_media_uri.'/image_meta/MyRadioImageMetadata/'
}

function coverFileFormat($temporary_file) {
    return explode('/',finfo_file(finfo_open(FILEINFO_MIME_TYPE), $tmp_file))[1];
}

function checkCoverFileUnique($path) {
    if (!file_exists($path)) {
        throw new MyRadioException('The cover filename chosen already exists.', 500);
    }
}

function moveCoverFileTo($path, $temporary_file) {
    move_uploaded_file($temporary_file, $path);
    if (!file_exists($path)) {
        throw new MyRadioException('File move failed.', 500);
    }
}

?>
