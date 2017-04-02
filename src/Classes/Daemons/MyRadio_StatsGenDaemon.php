<?php

namespace MyRadio\Daemons;

use MyRadio\Config;
use MyRadio\MyRadio\CoreUtils;
use MyRadio\ServiceAPI\MyRadio_User;
use MyRadio\ServiceAPI\MyRadio_TracklistItem;
use MyRadio\ServiceAPI\MyRadio_List;
use MyRadio\ServiceAPI\MyRadio_TrainingStatus;
use MyRadio\MyRadioEmail;

class MyRadio_StatsGenDaemon extends \MyRadio\MyRadio\MyRadio_Daemon
{
    public static function isEnabled()
    {
        return Config::$d_StatsGen_enabled;
    }

    public static function run()
    {
        $hourkey = __CLASS__.'_last_run_hourly';
        $daykey = __CLASS__.'_last_run_daily';
        if (self::getVal($hourkey) > time() - 3500) {
            return;
        }

        //Generate Training Graph
        self::generateTrainingGraph();

        //Do dailies?
        if (self::getVal($daykey) <= time() - 86300) {
            self::generateJukeboxReport();

            self::setVal($daykey, time());
        }

        //Done
        self::setVal($hourkey, time());
    }

    private static function generateTrainingGraph()
    {
        $outputbase = __DIR__ . '/../../Public/img/stats_training_';
        $statuses = MyRadio_TrainingStatus::getAll();

        foreach ($statuses as $status) {
            $awards = $status::getAwardedTo();
            $dotstr = 'digraph { overlap=false; splines=false; ';

            foreach ($awards as $award) {
                $by = $award->getAwardedBy()->getEmail();
                $to = $award->getAwardedTo()->getEmail();
                $date = date('Y-m-d', $award->getAwardedTime());
                $dotstr .= '"' . $by . '" -> "' . $to . '" [label="' . $date . '"]; ';
            }

            $dotstr .= '}';

            passthru("echo '$dotstr' | /bin/env sfdp -Tsvg > $outputbase" . $status->getID() . '.svg');
        }
    }

    /**
     * Once a day, this emails the Reporting List with a table of all tracks iTones has played in the last 24 hours
     * It's useful to see if it's got into bad habits such as playing the same song 3 million times.
     */
    private static function generateJukeboxReport()
    {
        //Review of whole week on Sundays
        if (date('N') == 7) {
            $info = MyRadio_TracklistItem::getTracklistStatsForJukebox(time() - (86400 * 7));
        } else {
            $info = MyRadio_TracklistItem::getTracklistStatsForJukebox(time() - 86400);
        }

        $totalplays = 0;
        $totaltracks = 0;
        $totaltime = 0;
        $table = '<table>';
        $table .= '<tr><th>Number of Plays</th><th>Title</th><th>Total Playtime</th><th>Playlist Membership</th></tr>';

        foreach ($info as $row) {
            $table .= '<tr><td>'.$row['num_plays'].'</td><td>'.$row['title'].'</td><td>'
                .$row['total_playtime'].'</td><td>'.$row['in_playlists'].'</td></tr>'."\r\n";
            $totalplays += $row['num_plays'];
            ++$totaltracks;
            $totaltime += $row['total_playtime'];
        }

        $table .= '<tr><th>'.$totalplays.'</th><th>'.$totaltracks
            .'</th><th>'.CoreUtils::intToTime($totaltime).'</th></tr>';
        $table .= '</table>';

        MyRadioEmail::sendEmailToList(
            MyRadio_List::getByName(Config::$reporting_list),
            'Jukebox Playout Report',
            $table
        );
    }
}
