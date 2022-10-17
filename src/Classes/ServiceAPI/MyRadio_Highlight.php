<?php

namespace MyRadio\ServiceAPI;

use MyRadio\Config;
use MyRadio\MyRadio\LoggerNGAPI;
use MyRadio\MyRadioException;
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
        $this->timeslot_id = (int) $data['show_season_timeslot_id'];
        $this->start_time = (int) $data['start_time'];
        $this->end_time = (int) $data['end_time'];
        $this->notes = (string) $data['notes'];
    }

    protected static function factory($itemid)
    {
        $sql = 'SELECT highlight_id, show_season_timeslot_id, start_time, end_time, notes FROM schedule.highlight WHERE highlight_id = $1 LIMIT 1';
        $data = self::$db->fetchOne($sql, [$itemid]);
        return new self($data);
    }


    /**
     * @param int|null $timeslot_id
     * @param int $attempts
     * @param string $notes
     * @return MyRadio_Highlight
     */
    public static function createFromLastSegment(int $timeslot_id = null, int $attempts = 10, string $notes = ''): MyRadio_Highlight
    {
        if ($timeslot_id === null) {
            $ts = MyRadio_Timeslot::getCurrentTimeslot();
            if ($ts === null) {
                throw new MyRadioException('No current timeslot', 400);
            }
        } else {
            $ts = MyRadio_Timeslot::getInstance($timeslot_id);
        }
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
                    // fallback
                    return self::create($ts->getID(), time() - 60*5, time(), $notes);
                }
            }
            $last = $tracklist[count($tracklist) - 1];
            if ($last->getEndTime() !== false) {
                if ($i <= $attempts) {
                    sleep(1);
                    $i++;
                    continue;
                } else {
                    // fallback
                    return self::create($ts->getID(), time() - 60*5, time(), $notes);
                }
            }
            $segmentStart = $tracklist[count($tracklist) - 2]->getEndTime();
            $segmentEnd = $last->getEndTime();
        } while ($segmentStart === null && $segmentEnd === null);
        return self::create($ts->getID(), $segmentStart, $segmentEnd, $notes);
    }

    /**
     * @param int $timeslot_id
     * @param int $start_time
     * @param int $end_time
     * @param string $notes
     * @return static
     */
    public static function create(int $timeslot_id, int $start_time, int $end_time, string $notes = ''): self
    {
        $sql = 'INSERT INTO schedule.highlight (show_season_timeslot_id, start_time, end_time, notes) VALUES ($1, $2, $3, $4) RETURNING highlight_id';
        $ret = self::$db->fetchColumn($sql, [$timeslot_id, CoreUtils::getTimestamp($start_time), CoreUtils::getTimestamp($end_time), $notes]);

        $hl =  self::getInstance($ret[0]);

        LoggerNGAPI::getInstance()->make($hl->getLogTitle(), $hl->start_time, $hl->end_time);

        return $hl;
    }

    private function getLogTitle(): string
    {
        return 'Highlight: ' . $this->getTimeslot()->getMeta('title') . ' ' . CoreUtils::happyTime($this->start_time);
    }

    public function hasAudioLog(): bool
    {
        try {
            LoggerNGAPI::getInstance()->download($this->getLogTitle(), $this->start_time, $this->end_time);
            return true;
        } catch (MyRadioException $e) {
            if ($e->getCode() === 403) {
                // lol loggerng
                return false;
            }
            throw $e;
        }
    }

    public function audioLogPath(): string
    {
        $res = LoggerNGAPI::getInstance()->download($this->getLogTitle(), $this->start_time, $this->end_time);
        return Config::$audio_logs_path . '/' . $res['filename_disk'];
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

    public function getID()
    {
        return $this->id;
    }
}
