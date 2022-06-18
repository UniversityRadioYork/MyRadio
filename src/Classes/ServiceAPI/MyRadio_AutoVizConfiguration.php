<?php

namespace MyRadio\ServiceAPI;

use MyRadio\MyRadio\CoreUtils;
use MyRadio\MyRadioException;

class MyRadio_AutoVizConfiguration extends ServiceAPI
{
    private int $autoviz_config_id;
    private int $show_season_timeslot_id;
    private bool $record;
    /** @type string|null */
    private $stream_url;
    /** @type string|null */
    private $stream_key;

    protected function __construct(array $data)
    {
                $this->autoviz_config_id = (int) $data['autoviz_config_id'];
        $this->show_season_timeslot_id = (int) $data['show_season_timeslot_id'];
        $this->record = $data['record'];
        $this->stream_url = $data['stream_url'];
        $this->stream_key = $data['stream_key'];
    }

    protected static function factory($itemid)
    {
        $data = self::$db->fetchOne(
            'SELECT * FROM schedule.autoviz_configuration
            WHERE autoviz_config_id = $1',
            [$itemid]
        );
        if (empty($data)) {
            throw new MyRadioException("AutoViz Configuration $itemid not found", 404);
        }
        return new self($data);
    }


    public static function getConfigForTimeslot($timeslot_id): ?MyRadio_AutoVizConfiguration
    {
        $data = self::$db->fetchOne(
            'SELECT * FROM schedule.autoviz_configuration
            WHERE show_season_timeslot_id = $1
            LIMIT 1',
            [$timeslot_id]
        );
        if (empty($data)) {
            return null;
        }
        return new self($data);
    }

    /**
     * @return int
     */
    public function getID(): int
    {
        return $this->autoviz_config_id;
    }

    public function getTimeslot(): MyRadio_Timeslot
    {
        return MyRadio_Timeslot::getInstance($this->show_season_timeslot_id);
    }

    /**
     * @return bool
     */
    public function getRecord(): bool
    {
        return $this->record;
    }

    /**
     * @return string|null
     */
    public function getStreamUrl()
    {
        return $this->stream_url;
    }

    /**
     * @return string|null
     */
    public function getStreamKey()
    {
        return $this->stream_key;
    }

    public static function create(int $timeslotID, bool $record, ?string $streamURL, ?string $streamKey): MyRadio_AutoVizConfiguration
    {
        if (($streamURL !== null && $streamKey === null) || ($streamURL === null && $streamKey !== null)) {
            throw new MyRadioException('Must specify both stream URL and key', 400);
        }
        $result = self::$db->fetchColumn(
            'INSERT INTO schedule.autoviz_configuration (show_season_timeslot_id, record, stream_url, stream_key)
                VALUES ($1, $2, $3, $4)
                RETURNING autoviz_config_id',
            [$timeslotID, $record, $streamURL, $streamKey]
        );
        return self::getInstance((int)$result[0]);
    }

    public function update(bool $record, ?string $streamURL, ?string $streamKey)
    {
        if (($streamURL !== null && $streamKey === null) || ($streamURL === null && $streamKey !== null)) {
            throw new MyRadioException('Must specify both stream URL and key', 400);
        }
        if (!$record && $streamURL === null && $streamKey === null) {
            // Just delete the config
            self::$db->query('DELETE FROM schedule.autoviz_configuration WHERE autoviz_config_id = $1', [$this->autoviz_config_id]);
            self::$cache->delete(self::getCacheKey($this->getID()));
        } else {
            self::$db->query(
                'UPDATE schedule.autoviz_configuration
                SET record = $2, stream_url = $3, stream_key = $4
                WHERE autoviz_config_id = $1',
                [$this->autoviz_config_id, $record, $streamURL, $streamKey]
            );
            $this->updateCacheObject();
        }
    }

    public function toDataSource($mixins = [])
    {
        return [
            'autoviz_config_id' => $this->autoviz_config_id,
            'timeslot' => $this->getTimeslot()->toDataSource($mixins),
            'record' => $this->record,
            'stream_url' => $this->stream_url,
            'stream_key' => $this->stream_key,
        ];
    }

    /**
     * Returns an array representing this configuration in the format expected by the autoviz software.
     * @return array
     */
    public function toTask(): array
    {
        $ts = $this->getTimeslot();
        $task = [
            'name' => $ts->getMeta('title') . ' - ' . CoreUtils::happyTime($ts->getStartTime()),
            'timeslotid' => $ts->getID(),
            'startTime' => CoreUtils::getIso8601Timestamp($ts->getStartTime()),
            'endTime' => CoreUtils::getIso8601Timestamp($ts->getEndTime()),
            'record' => $this->record
        ];
        if (!empty($this->stream_url) && !empty($this->stream_key)) {
            $task['stream'] = [
                'url' => $this->stream_url,
                'key' => $this->stream_key
            ];
        } else {
            $task['stream'] = false;
        }
        return $task;
    }
}
