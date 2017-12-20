const frisby = require("frisby");
const Joi = frisby.Joi;
const url = require("./lib/url");

frisby.globalSetup({
  request: {
    headers: {"Accept": "application/json"},
    inspectOnFailure: true
  }
});

var user = {
  fname: "Travis",
  sname: "The Tester",
  email: "showspecdevnull" + Date.now() + "@example.com",
  receive_email: false
};

var show = {
  title: "Travis likes to test shows.",
  description: "Travis is a busy guy, but in his spare time, he does his own radio show to test what this on the air malarkey is all about.",
};

describe("Create a show", function() {
  it("should create a test member", function(done) {
    frisby.post(url.base + "user?api_key=travis-test-key", user, {json: true})
      .expect("status", 201)
      .done(done);
  });

  it("should create a test show", function(done) {
    var memberid = json.payload.memberid;
    // This is the format the create form submites :(
    show.credits = {credittype: [1], memberid: [memberid]};

    frisby.post(url.base + "show?api_key=travis-test-key", show, {json: true})
      .expect("status", 201)
      .expect("header", {
        "content-type": "application/json"
      })
      .expect("json", "*", {
        status: "OK",
        payload: {
          title: show.title,
          description: show.description
        }
      })
      .expect("jsonTypes", "*", {
        time: Joi.string(),
        payload: {
          show_id: Joi.number(),
          credits_string: Joi.string(),
        }
      })
      .done(done);
  });

  it("should have no seasons", function(done) {
    frisby.get(url.base + "show/" + showid + "/numberofseasons?api_key=travis-test-key")
      .expect("status", 200)
      .expect("header", {
        "content-type": "application/json",
      })
      .expect("json", "*", {
        status: "OK",
        payload: 0
      })
      .expect("jsonTypes", "*", {
        time: Joi.string(),
      })
      .done(done);
  });

  it("should have a credit", function(done) {
    frisby.get(url.base + "show/" + showid + "/credits?api_key=travis-test-key")
      .expect("status", 200)
      .expect("header", {
        "content-type": "application/json",
      })
      .expect("json", "*", {
        status: "OK",
        payload: [{memberid: memberid}]
      })
      .expect("jsonTypes", "*", {
        time: Joi.string(),
      })
      .done(done);
  });

  it("should appear in the All Shows list", function(done) {
    frisby.get(url.base + "show/allshows?api_key=travis-test-key")
      .expect("status", 200)
      .expect("header", {
        "content-type": "application/json",
      })
      .expect("json", "payload.*", {
        show_id: showid,
        title: show.title,
        description: show.description
      })
      .expect("jsonTypes", "*", {
        time: Joi.string(),
      })
      .done(done);
  });
});
