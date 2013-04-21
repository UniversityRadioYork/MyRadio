<?php
/**
 * Scan music library, filling in blanks or changing default values
 * @author Lloyd Wallis <lpw@ury.york.ac.uk>
 * @version 21042013
 * @package MyURY_Library
 */

$albums = MyURY_Album::findByName('URY Downloads', 10);

$limit = 5000;
$counter = 0;
foreach ($albums as $album) {
  $tracks = $album->getTracks();
  foreach ($tracks as $track) {
    
  }
}

require 'Views/MyURY/Library/bootstrap.php';
$twig->setTemplate('table.twig')
        ->addVariable('tablescript', 'myury.library.gapfiller')
        ->addVariable('title', 'Members List')
        ->addVariable('tabledata', $members)
        ->render();