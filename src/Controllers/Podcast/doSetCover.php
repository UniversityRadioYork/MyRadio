<?php

/**
 * Sets a podcast's cover.
 *
 * @author  Matt Windsor <matt.windsor@ury.org.uk>
 * @version 20140117
 * @package MyRadio_Podcasts
 */

require_once 'common.php';

$values = podcastCoverForm()->readValues();
$podcast = currentPodcast($values);
raisePermissionsIfCannotEdit($podcast);

switch ($values['cover_method']) {
    case 'existing':
        existingCoverFile($podcast, $values);
        break;
    case 'new':
        uploadNewCoverFile($podcast, $values);
        break;
    default:
        throw new MyRadioException('Unknown cover upload method.', 400);
}


//
// Helper functions
//

function existingCoverFile($podcast, $values)
{
    $podcast->setCover($values['existing_cover']);
}

function uploadNewCoverFile($podcast, $values)
{
    $temporary = $values['new_cover']['tmp_name'];
    if (empty($temporary)) {
        throw new MyRadioException('No new cover file uploaded.', 400);
    }

    $url = moveCoverFile($podcast, $temporary);

    $podcast->setCover($url);
}

function moveCoverFile($podcast, $temporary_file)
{
    $path = makeCoverFilePath($podcast, $temporary_file);
    $file_path = Config::$public_media_path . $path;
    checkCoverFileUnique($file_path);
    moveCoverFileTo($file_path, $temporary_file);

    return $path;
}

function makeCoverFilePath($podcast, $temporary_file)
{
    return (
        coverFileDirectory() .
        'podcast' .
        $podcast->getID() .
        '-' .
        time() .
        '.' .
        coverFileFormat($temporary_file)
    );
}

function coverFileDirectory()
{
    return '/image_meta/MyRadioImageMetadata/';
}

function coverFileFormat($temporary_file)
{
    return explode(
        '/',
        finfo_file(finfo_open(FILEINFO_MIME_TYPE), $temporary_file)
    )[1];
}

function checkCoverFileUnique($path)
{
    if (file_exists($path)) {
        throw new MyRadioException('The cover filename chosen already exists.', 500);
    }
}

function moveCoverFileTo($path, $temporary_file)
{
    move_uploaded_file($temporary_file, $path);
    if (!file_exists($path)) {
        throw new MyRadioException('File move failed.', 500);
    }
}

coreUtils::backWithMessage('Cover set.');
