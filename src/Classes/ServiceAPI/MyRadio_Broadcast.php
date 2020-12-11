<?php

namespace MyRadio\ServiceAPI;

use MyRadio\MyRadio\CoreUtils;
use MyRadio\MyRadioException;

class MyRadio_Broadcast extends ServiceAPI
{

    private $broadcast_id;

    private $member;

    private $path;

    private $time;

    protected function __construct($broadcast_id)
    {
        if (empty($broadcast_id)) {
            throw new MyRadioException(("Broadcast ID must be provided"));
        }


        $this->broadcast_id = (int)$broadcast_id;
        self::initDB();

        $result = self::$db-> fetchOne(
            "SELECT * FROM public.broadcast
            WHERE broadcast_id = $1", [$broadcast_id]
        );

        if (empty($result)) {
            throw new MyRadioException(
                "The MyRadio_Broadcast with ID " . $broadcast_id . " doesn't exist.",
                400
            );
        }

        $this->member = MyRadio_User::getInstance((int)$result['memberid']);
        $this->path = $result['path'];
        $this->time = strtotime($result['time']);
    }

    public function getMember(){
        return $this->member;
    }

    public function getPath(){
        return $this->path;
    }

    public static function getUserBroadcast($memberid){
        $result = self::$db->fetchColumn(
            "SELECT broadcast_id FROM public.broadcast
            WHERE member_id = $1
            AND time > (NOW() - interval \'1 day\')",
            [
                $memberid
            ]
            );
        if (empty($result)){
            return;
        } else{
            return self::resultSetToObjArray($result);
        }
    }

    public static function getUsersWithBroadcasts() {
        $result = self::$db->fetchColumn(
            "SELECT DISTINCT member_id FROM public.broadcast
            WHERE time > (NOW() - interval \'1 day\')"
        );
        return MyRadio_User::resultSetToObjArray($result);
    }

    public static function create(
        $path
    ) {
        if (!isset($path)){
            throw new MyRadioException("Path must be specified", 500);
        }
        self::$db->query(
            "INSERT INTO public.broadcast
            VALUES ($1, $2, NOW())",
            [
                $_SESSION['memberid'],
                $path
            ]
            );
    }

}