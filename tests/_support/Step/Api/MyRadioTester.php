<?php
namespace Step\Api;

use \Codeception\Util\HttpCode;

class MyRadioTester extends \ApiTester
{

    public function checkAPIResponse($status_code=HttpCode::OK)
    {
        $I = $this;
        $I->seeResponseCodeIs($status_code);
        $I->haveHttpHeader("Content-Type", "application/json");
        $I->seeResponseIsJson();
        $I->seeResponseContainsJson([
            "status" => "OK",
        ]);
        $I->seeResponseMatchesJsonType([
            "time" => "string"
        ]);
    }
}
