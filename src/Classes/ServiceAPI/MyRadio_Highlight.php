<?php

namespace MyRadio\ServiceAPI;

use MyRadio\ServiceAPI\ServiceAPI;
use MyRadio\ServiceAPI\MyRadio_Timeslot;
use MyRadio\MyRadio\CoreUtils;

class MyRadio_Highlight extends ServiceAPI
{
    private int $id;
    private int $timeslot_id;
    private int $start_time;
    private int $end_time;
    private string $notes;

    public function __construct(array $data)
    {
        $this->id = (int) $data['highlight_id'];
        $this->timeslot_id = (int) $data['timeslot_id'];
        $this->start_time = (int) $data['start_time'];
        $this->end_time = (int) $data['end_time'];
        $this->notes = (string) $data['notes'];
    }

    public static function createFromLastSegment(int $timeslot_id, int $attempts = 10, string $notes = ''): MyRadio_Highlight
    {
        $ts = MyRadio_Timeslot::getInstance($timeslot_id);
        $i = 0;
        $segmentStart = null;
        $segmentEnd = null;
        do {
            $tracklist = MyRadio_TracklistItem::getTracklistForTimeslot($ts);
            if (count($tracklist) < 2) {
                if ($i <= $attempts) {
                    sleep(1);
                    $i++;
                    continue;
                } else {
                    goto fallback;
                }
            }
            $last = $tracklist[count($tracklist) - 1];
            if ($last->getEndTime() !== false) {
                if ($i <= $attempts) {
                    sleep(1);
                    $i++;
                    continue;
                } else {
                    goto fallback;
                }
            }
            $segmentStart = $tracklist[count($tracklist) - 2]->getEndTime();
            $segmentEnd = $last->getEndTime();
        } while ($segmentStart === null && $segmentEnd === null);
        return self::create($timeslot_id, $segmentStart, $segmentEnd, $notes);
    fallback:
        return self::create($timeslot_id, time() - 60*5, time(), $notes);
    }

    public static function create(int $timeslot_id, int $start_time, int $end_time, $notes = ''): self
    {
        $sql = 'INSERT INTO schedule.highlight (show_season_timeslot_id, start_time, end_time, notes) VALUES ($1, $2, $3, $4) RETURNING highlight_id';
        $ret = self::$db->fetchColumn($sql, [$timeslot_id, CoreUtils::getTimestamp($start_time), CoreUtils::getTimestamp($end_time), $notes]);

        return self::getInstance($ret[0]);
    }

    public static function getHighlightsForTimeslot(int $timeslot_id): array
    {
        $sql = 'SELECT * FROM schedule.highlight WHERE show_season_timeslot_id = $1 ORDER BY start_time';
        $rows = self::$db->fetchAll($sql, [$timeslot_id]);
        $result = [];
        foreach ($rows as $row) {
            $result[] = new self($row);
        }
        return $result;
    }

    /**
     * @return self[]
     */
    public static function getLastHighlightsForCurrentUser(int $n = 25): array
    {
        $sql = 'SELECT highlight.* FROM schedule.highlight
            LEFT JOIN schedule.show_season_timeslot ts USING (show_season_timeslot_id)
            WHERE (memberid = $1
            OR $1 in (
                SELECT memberid from schedule.show_credit
                WHERE show_id = (
                        SELECT show_id FROM schedule.show_season_timeslot
                        JOIN schedule.show_season USING (show_season_id)
                        WHERE show_season_timeslot_id=ts.show_season_timeslot_id
                    )
                AND effective_from < (ts.start_time + duration)
                                AND (effective_to IS NULL OR effective_to > ts.start_time)
                                AND approvedid IS NOT NULL
                ))
            ORDER BY ts.start_time DESC
            LIMIT $2';
        $rows = self::$db->fetchAll($sql, [$_SESSION['memberid'], $n]);
        $result = [];
        foreach ($rows as $row) {
            $result[] = new self($row);
        }
        return $result;
    }

    public function getTimeslot(): MyRadio_Timeslot
    {
        return MyRadio_Timeslot::getInstance($this->timeslot_id);
    }

    private const autoVizClipTimeLeeway = 10; // 10 seconds
    public function getAutoVizClip(): ?MyRadio_AutoVizClip
    {
        $clips = MyRadio_AutoVizClip::getClipsForTimeslot($this->timeslot_id);
        foreach ($clips as $clip) {
            if ($clip->getStartTime() >= ($this->start_time - self::autoVizClipTimeLeeway) && $clip->getEndTime() <= ($this->end_time + self::autoVizClipTimeLeeway)) {
                return $clip;
            }
        }
        return null;
    }

    public function toDataSource($mixins = [])
    {
        $clip = $this->getAutoVizClip();
        return [
            'highlight_id' => $this->id,
            'timeslot' => $this->getTimeslot()->toDataSource($mixins),
            'start_time' => CoreUtils::happyTime($this->start_time),
            'end_time' => CoreUtils::happyTime($this->end_time),
            'notes' => $this->notes,
            'autoviz_clip' => $clip === null ? null : $clip->toDataSource($mixins)
        ];
    }
}
