<?php

namespace MyRadio\ServiceAPI;

use MyRadio\MyRadio\CoreUtils;
use MyRadio\ServiceAPI\MyRadio_User;
use MyRadio\MyRadioException;

class MyRadio_AuditLog extends ServiceAPI
{
    private const BASE_SQL = "SELECT log_entry_id, entry_type, target_class, target_id, actor_id, payload, entry_time FROM myradio.audit_log";

    private int $entry_id;

    private string $entry_type;

    /** @var string|null */
    private $target_class;

    private int $target_id;

    private int $actor_id;

    private array $payload;

    private int $entry_time;

    protected function __construct(array $data)
    {
        $this->entry_id = (int) $data['log_entry_id'];
        $this->entry_type = $data['entry_type'];
        $this->target_class = $data['target_class'];
        $this->target_id = (int) $data['target_id'];
        $this->actor_id = (int) $data['actor_id'];
        $this->payload = json_decode($data['payload'], true);
        $this->entry_time = strtotime($data['entry_time']);
    }

    public function getID()
    {
        return $this->entry_id;
    }

    public function getEventType()
    {
        return $this->entry_type;
    }

    public function getTargetClass()
    {
        return $this->target_class;
    }

    public function getTargetID()
    {
        return $this->target_id;
    }

    /**
     * @return MyRadio\ServiceAPI\MyRadio_User
     */
    public function getActor()
    {
        return MyRadio_User::getInstance($this->actor_id);
    }

    public function getPayload()
    {
        return $this->payload;
    }

    public function getEntryTime()
    {
        return $this->entry_time;
    }

    /**
     * Logs an event to the audit log, attributing it to the currently signed in user.
     * 
     * If no $target_class is given, uses the calling class name.
     */
    public static function log(string $type, int $target_id, array $payload, string $target_class='')
    {
        if ($target_class === '')
        {
            $caller = debug_backtrace(!DEBUG_BACKTRACE_PROVIDE_OBJECT|DEBUG_BACKTRACE_IGNORE_ARGS,2)[1];
            $target_class = $caller['class'];
        }
        $actor_id = MyRadio_User::getCurrentOrSystemUser()->getID();
        $sql = "INSERT INTO myradio.audit_log (entry_type, target_class, target_id, actor_id, payload, entry_time) VALUES ($1, $2, $3, $4, $5::JSONB, NOW())";
        self::$db->query(
            $sql,
            [$type, $target_class, $target_id, $actor_id, json_encode($payload)]
        );
    }

    /**
     * @return self[]
     */
    public static function getEvents(int $since, int $until, array $query = [])
    {
        $sql = self::BASE_SQL . ' WHERE entry_time >= $1 AND entry_time <= $2';
        $paramId = 0;
        $params = [CoreUtils::getTimestamp($since), CoreUtils::getTimestamp($until)];
        if (in_array('event_type', $query))
        {
            $sql .= " AND event_type = $$paramId";
            $params[] = $query['event_type'];
            $paramId++;
        }
        if (in_array('target_type', $query))
        {
            $sql .= " AND target_type = $$paramId";
            $params[] = $query['target_type'];
            $paramId++;
        }
        if (in_array('actor_id', $query))
        {
            $sql .= " AND actor_id = $$paramId";
            $params[] = $query['actor_id'];
            $paramId++;
        }

        $rows = self::$db->fetchAll($sql, $params);
        $result = [];
        foreach ($rows as $row)
        {
            $result[] = new self($row);
        }
        return $result;
    }

    protected static function factory($itemid)
    {
        $sql = self::BASE_SQL . " WHERE eventid = $1";

        $result = self::$db->fetchOne($sql, [$itemid]);
        if (empty($result)) {
            throw new MyRadioException("Event $itemid does not exist", 404);
        }

        return new self($result);
    }
}
