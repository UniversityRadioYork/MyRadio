<?php

// Test if the API responds to queries
//  TODO Expand this test file and explain what it's for
class SanitySpecCest
{
    public function _before(ApiTester $I)
    {
    }

    public function _after(ApiTester $I)
    {
    }

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
