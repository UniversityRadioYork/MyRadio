<?php
/**
 * Tracklist Track Inserter for SIS
 *
 * @author Andy Durant <aj@ury.org.uk>
 * @version 20131101
 * @package MyRadio_SIS
 */

$artist = $_GET['artist'];
$album = $_GET['album'];
$tname = $_GET['tname'];
$where = $_GET['where'];

$timeslotid = $_SESSION['timeslotid'];

if ($where == "notrec") {
    SIS_Tracklist::insertTrackNoRec($tname, $artist, $album, time(), "m", $timeslotid);
    header('HTTP/1.1 204 No Content');
} elseif ($where == 'rec') {
    $result = SIS_Tracklist::checkTrackOK($artist, $album, $tname);
    $numrow = sizeof($result);
    $row = $result[0];
    $return = 0;
    if ($numrow != 1) {
        if ($numrow == 0) {
            $return = 1;
        } elseif ($numrow >= 2) {
            $return = 2;
        }
    } elseif ($numrow == 1) {
        SIS_Tracklist::insertTrackRec($row['trackid'], $row['recordid'], time(), "m", $timeslotid);
        $return = 0;
    }

    $data = ["return"=>$return, "result"=>$row];
    //Return the response data
    CoreUtils::dataToJSON($data);
}
