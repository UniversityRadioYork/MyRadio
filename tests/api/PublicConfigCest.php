<?php


class PublicConfigCest
{
    public function _before(ApiTester $I)
    {
    }

    public function _after(ApiTester $I)
    {
    }

    // tests
    public function getConfig(ApiTester $I)
    {
        $I->wantTo("retrieve the public config");
        $I->sendGET("publicconfig?api_key=travis-test-key");
        $I->seeResponseCodeIs(200);
        $I->seeResponseIsJson();
    }
}
