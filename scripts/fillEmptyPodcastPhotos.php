<?php

// Filling Podcasts without photos with photos

use MyRadio\ServiceAPI\MyRadio_Podcast;

$allPods = MyRadio_Podcast::getAllPodcasts();

foreach ($allPods as $pod) {
    if ($pod->getCover() == null) {
        $pod->setCover("/media/image_meta/PodcastImageMetadata/default.png");
    }
}