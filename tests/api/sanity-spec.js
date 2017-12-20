const frisby = require("frisby");
const Joi = frisby.Joi;
const url = require("./lib/url");

frisby.globalSetup({
  request: {
    headers: {"Accept": "application/json"},
    inspectOnFailure: true
  }
});

describe("Public config", function() {
  it("should get", function(doneFn) {
    frisby.get(url.base + "config/publicconfig?api_key=travis-test-key")
      .expect("status", 200)
      .expect("header", {
        "content-type": "application/json"
      })
      .expect("json", "*", {
        status: "OK",
        payload: {
          short_name: "URN"
        }
      })
      .expect("jsonTypes", "*", {
        time: Joi.string(),
      })
      .done(doneFn);
  });
});
