<?php

use \Codeception\Util\HttpCode;

class ShowSpecCest
{
    public static $user = [
        "fname" => "Testy",
        "sname" => "McTestFace",
        "receive_email" => false
    ];
    public static $show = [
        "title" => "Testy likes to test shows.",
        "description" => "Testy is a busy guy, but in his spare time, he does his own radio show to test what this on the air malarky is all about.",
        "subtype" => "regular",
        "podcast_explicit" => false
    ];

    public function _before(\Step\Api\MyRadioTester $I)
    {
        $time = time();
        // Obj has to be constructed here, for the time() call
        ShowSpecCest::$user["email"] = "showspec$time@example.com";

        $I->sendPOST("/user?api_key=travis-test-key", ["user" => ShowSpecCest::$user]);
        $I->seeResponseCodeIs(HttpCode::CREATED); // User creation testing is done elsewhere
        ShowSpecCest::$user["id"] = $I->grabDataFromResponseByJsonPath("$.payload.memberid")[0];

        $I->haveHttpHeader('Content-Type', 'application/json'); // Necessary for post requests
    }

    public function _after(\Step\Api\MyRadioTester $I)
    {
    }

    // tests
    public function testShowCreate(\Step\Api\MyRadioTester $I)
    {
        ShowSpecCest::$show["credits"] = [
            "credittype" => [1], // Show creation requires these extra arrays :(
            "memberid" => [ShowSpecCest::$user["id"]],
        ];
        $I->wantTo("create a show");
        $I->sendPOST("/show?api_key=travis-test-key", ShowSpecCest::$show);
        $I->checkAPIResponse(HttpCode::CREATED);
        $I->seeResponseContainsJson([
            "payload" => [
                "title" => ShowSpecCest::$show["title"],
                "description" => ShowSpecCest::$show["description"],
            ],
        ]);
        $I->seeResponseMatchesJsonType([
            "payload" => [
                "show_id" => "integer",
                "credits_string" => "string",
            ],
        ]);

        $I->wantTo("check the show has no seasons");
        $showid = $I->grabDataFromResponseByJsonPath("$.payload.show_id")[0];
        $I->sendGET("/show/".$showid."/numberofseasons?api_key=travis-test-key");
        $I->checkAPIResponse();
        $I->seeResponseContainsJson([
            "payload" => 0,
        ]);

        $I->wantTo("check the show has a credit");
        $I->sendGET("/show/".$showid."/credits?api_key=travis-test-key");
        $I->checkAPIResponse();
        $I->seeResponseContainsJson([
            "payload" => ["memberid" => ShowSpecCest::$user["id"]],
        ]);

        $I->wantTo("see the show in the All Shows list");
        $I->sendGET("/show/allshows?api_key=travis-test-key");
        $I->checkAPIResponse();
        $I->seeResponseContainsJson([
            "payload" => [
                [
                    "show_id" => $showid,
                    "title" => ShowSpecCest::$show["title"],
                    "description" => ShowSpecCest::$show["description"],
                ],
            ],
        ]);
    }
}
