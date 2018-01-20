<?php


class SanitySpecCest
{
    public function _before(ApiTester $I)
    {
    }

    public function _after(ApiTester $I)
    {
    }

    // tests
    public function getConfig(\Step\Api\MyRadioTester $I)
    {
        $I->wantTo("retrieve the public config");
        $I->sendGET("/config/publicconfig?api_key=travis-test-key");
        $I->checkAPIResponse();
        $I->seeResponseContainsJson([
            "payload" => [
                "short_name" => "URN",
            ],
        ]);
    }
}
