<?php


namespace MyRadio\ServiceAPI;


use MyRadio\MyRadioException;

class MyRadio_UserOfficership extends ServiceAPI
{
    private $id;
    private $memberid;
    private $officerid;
    private $from_date;
    private $till_date;

    public function __construct($data)
    {
        parent::__construct();
        $this->id = $data['member_officerid'];
        $this->memberid = $data['memberid'];
        $this->officerid = $data['officerid'];
        $this->from_date = strtotime($data['from_date']);
        $this->till_date = empty($data['till_date']) ? null : strtotime($data['till_date']);
    }

    /**
     * @return int
     */
    public function getID() {
        return $this->id;
    }

    /**
     * @return MyRadio_User
     */
    public function getUser()
    {
        return MyRadio_User::getInstance($this->memberid);
    }

    /**
     * @return MyRadio_Officer
     */
    public function getOfficer()
    {
        return MyRadio_Officer::getInstance($this->officerid);
    }

    /**
     * @return int
     */
    public function getFromDate()
    {
        return $this->from_date;
    }

    /**
     * @return int|null
     */
    public function getTillDate()
    {
        return $this->till_date;
    }

    protected static function factory($itemid)
    {
        $data = self::$db->fetchOne(
            'SELECT member_officerid, memberid, officerid, from_date, till_date
            FROM public.member_officer
            WHERE member_officerid = $1',
            [$itemid]
        );

        if (empty($data)) {
            throw new MyRadioException("Couldn't track down MyRadio_UserOfficership#$itemid", 404);
        }
        return new self($itemid);
    }

    public function toDataSource($mixins = [])
    {
        return [
            'id' => $this->id,
            'member' => $this->getUser()->toDataSource($mixins),
            'officer' => $this->getOfficer()->toDataSource($mixins),
            'from_date' => $this->from_date,
            'till_date' => $this->till_date
        ];
    }


}
