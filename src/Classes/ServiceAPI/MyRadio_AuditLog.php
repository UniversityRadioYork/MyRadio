<?php

namespace MyRadio\ServiceAPI;

use MyRadio\ServiceAPI\MyRadio_User;
use MyRadio\MyRadioException;

class MyRadio_AuditLog extends ServiceAPI
{
    private const BASE_SQL = "SELECT entry_id, entry_type, target_class, target_id, actor_id, payload, entry_time FROM myradio.audit_log";

    private int $entry_id;

    private string $entry_type;

    private string $target_class;

    private int $target_id;

    private int $actor_id;

    private array $payload;

    private int $entry_time;

    protected function __construct(array $data)
    {
        $this->entry_id = (int) $data['entry_id'];
        $this->entry_type = $data['entry_type'];
        $this->target_class = $data['target_class'];
        $this->target_id = (int) $data['target_id'];
        $this->actor_id = (int) $data['actor_id'];
        $this->payload = $data['payload'];
        $this->entry_time = (int) $data['entry_time'];
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
            $caller = debug_backtrace(0, 1);
            $target_class = $caller['class'];
        }
        $actor_id = MyRadio_User::getCurrentOrSystemUser()->getID();
        $sql = "INSERT INTO myradio.audit_log (entry_type, target_class, target_id, actor_id, payload, entry_time) VALUES ($1, $2, $3, $4, $5::JSONB, NOW())";
        self::$db->query(
            $sql,
            [$type, $target_class, $target_id, $actor_id, json_encode($payload)]
        );
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
