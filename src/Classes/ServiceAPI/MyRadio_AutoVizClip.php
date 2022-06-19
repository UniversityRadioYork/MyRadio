<?php

namespace MyRadio\ServiceAPI;

use MyRadio\Config;
use MyRadio\MyRadio\CoreUtils;
use MyRadio\MyRadioException;

class MyRadio_AutoVizClip extends ServiceAPI
{
    /*
     * Clip file naming conventions:
     * - OBS work in progress recordings are YYYY-MM-DD hh-mm-ss.mkv
     * - Transcoded complete show clip is full_show-START_TIME_UNIX_SECONDS-END_TIME_UNIX_SECONDS.mp4
     * - Individual clips are clip-START_TIME_UNIX_SECONDS-END_TIME_UNIX_SECONDS.mp4
     * (In other words, CLIP_TYPE-START_TIME-END_TIME.mp4)
     */

    /**
     * Either 'full_show' or 'clip'.
     */
    private string $type;
    private int $start_time;
    private int $end_time;
    private int $timeslot_id;
    /**
     * The relative file name
     */
    private string $filename;

    public function getType(): string
    {
        return $this->type;
    }

    public function getStartTime(): int
    {
        return $this->start_time;
    }

    public function getEndTime(): int
    {
        return $this->end_time;
    }

    public function getTimeslotID(): int
    {
        return $this->timeslot_id;
    }

    public function getPublicURL(): string
    {
        return Config::$autoviz_public_clips_base . '/' . $this->timeslot_id . '/' . $this->filename;
    }

    /**
     * @return {AutoVizClip[]}
     */
    public static function getClipsForTimeslot(int $timeslot_id): array
    {
        if (!is_int($timeslot_id)) {
            // Path traversal would be very bad indeed!
            throw new MyRadioException('Timeslot ID must be an int!');
        }
        $path = Config::$autoviz_clips_path . '/' . $timeslot_id;
        if (!is_dir($path)) {
            return [];
        }

        $paths = scandir($path);
        if ($paths === false) {
            throw new MyRadioException("Failed to list clips for timeslot $timeslot_id");
        }
        $result = [];
        foreach ($paths as $clipPath) {
            if ($clipPath[0] === '.') {
                continue;
            }
            if (str_ends_with($clipPath, '.mkv')) {
                // OBS complete show recording, ignore it
                continue;
            }
            $clip = new self();
            $parts = explode('-', $clipPath, 3);
            switch ($parts[0]) {
                case 'full_show':
                case 'clip':
                    $clip->type = $parts[0];
                    break;
                default:
                    throw new MyRadioException("Unrecognised clip type for file $clipPath");
            }
            $clip->start_time = intval($parts[1]);
            $clip->end_time = intval($parts[2]);
            $clip->timeslot_id = $timeslot_id;
            $clip->filename = $clipPath;
            $result[] = $clip;
        }
        return $result;
    }

    public function toDataSource($mixins = [])
    {
        return [
            'type' => $this->type,
            'startTime' => CoreUtils::happyTime($this->start_time),
            'endTime' => CoreUtils::happyTime($this->end_time),
            'timeslot' => MyRadio_Timeslot::getInstance($this->timeslot_id)->toDataSource($mixins)
        ];
    }
}
