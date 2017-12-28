<?php


class ShowSpecCest
{
    public static $user = [
        "fname" => "Testy",
        "sname" => "McTestFace",
        "receive_email" => false
    ];
    public static $show = [
        "title" => "Testy likes to test shows.",
        "description" => "Testy is a busy guy, but in his spare time, he does his own radio show to test what this on the air malarky is all about."
    ];

    public function _before(ApiTester $I)
    {
        $time = time();
        // Obj has to be constructed here, for the time() call
        ShowSpecCest::$user["email"] = "showspec$time@example.com";

        $I->sendPOST("/user?api_key=travis-test-key", ["user" => ShowSpecCest::$user]);
        $I->seeResponseCodeIs(201); // User creation testing is done elsewhere
        ShowSpecCest::$user["id"] = $I->grabDataFromResponseByJsonPath("$.payload.memberid");

        $I->haveHttpHeader('Content-Type', 'application/json'); // Necessary for post requests
    }

    public function _after(ApiTester $I)
    {
    }

    // tests
    public function testShowCreate(ApiTester $I)
    {
        ShowSpecCest::$show["credits"] = [
            "credittype" => 1,
            "memberid" => [ShowSpecCest::$user["id"]], // Extra array is necessary here...
        ];
        $I->wantTo("create a show");
        $I->sendPOST("/show?api_key=travis-test-key", ShowSpecCest::$show);
        $I->seeResponseCodeIs(201);
        $I->haveHttpHeader("Content-Type", "application/json");
        $I->seeResponseIsJson();
        $I->seeResponseContainsJson([
            "status" => "OK",
            "payload" => [
                "title" => ShowSpecCest::$show["title"],
                "description" => ShowSpecCest::$show["description"],
            ],
        ]);
        $I->seeResponseMatchesJsonType([
            "time" => "string",
            "payload" => [
                "show_id" => "integer",
                "credits_string" => "string",
            ],
        ]);

        $I->wantTo("check the show has no seasons");
        $showid = $I->grabDataFromResponseByJsonPath("$.payload.show_id");
        $I->sendGET("/show/".$showid."/numberofseasons?api_key=travis-test-key");
        $I->seeResponseCodeIs(200);
        $I->haveHttpHeader("Content-Type", "application/json");
        $I->seeResponseIsJson();
        $I->seeResponseContainsJson([
            "payload" => 0,
        ]);

        $I->wantTo("check the show has a credit");
        $I->sendGET("/show/".$showid."/credits?api_key=travis-test-key");
        $I->seeResponseCodeIs(200);
        $I->haveHttpHeader("Content-Type", "application/json");
        $I->seeResponseIsJson();
        $I->seeResponseContainsJson([
            "payload" => ["memberid" => ShowSpecCest::$user["id"]],
        ]);

        $I->wantTo("see the show in the All Shows list");
        $I->sendGET("/show/allshows?api_key=travis-test-key");
        $I->seeResponseCodeIs(200);
        $I->haveHttpHeader("Content-Type", "application/json");
        $I->seeResponseIsJson();
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
