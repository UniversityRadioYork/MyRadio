var frisby = require('frisby');

frisby.globalSetup({
  request: {
    headers: {'Accept': 'application/json'},
    inspectOnFailure: true
  }
});

var user = {
  fname: 'Travis',
  sname: 'The Tester',
  email: 'showspecdevnull' + Date.now() + '@example.com',
  receive_email: false
};

var show = {
  title: 'Travis likes to test shows.',
  description: 'Travis is a busy guy, but in his spare time, he does his own radio show to test what this on the air malarkey is all about.',
};

frisby.create('Create a test member')
  .post('http://localhost:7080/api/v2/user?api_key=travis-test-key', user, {json: true})
  .expectStatus(201)
  .afterJSON(function(json) {
    var memberid = json.payload.memberid;
    show.credits = [{type: 1, memberid: memberid}];

    frisby.create('Create a test show')
      .post('http://localhost:7080/api/v2/show?api_key=travis-test-key', show, {json: true})
      .expectStatus(201)
      .expectHeaderContains('content-type', 'application/json')
      .expectJSON({
        status: 'OK',
        payload: {
          title: show.title,
          description: show.description
        }
      })
      .expectJSONTypes({
        time: String,
        payload: {
          show_id: Number,
          // Don't like, but backwards compatibility :(
          credits: String
        }
      })
      .afterJSON(function(json) {
        var showid = json.payload.show_id;

        frisby.create('The show should have no seasons')
          .get('http://localhost:7080/api/v2/show/' + showid + '/numberofseasons?api_key=travis-test-key')
          .expectStatus(200)
          .expectHeaderContains('content-type', 'application/json')
          .expectJSON({
            status: 'OK',
            payload: 0
          })
          .expectJSONTypes({
            time: String
          })
          .toss();

        frisby.create('The show should have a credit')
          .get('http://localhost:7080/api/v2/show/' + showid + '/credits?api_key=travis-test-key')
          .expectStatus(200)
          .expectHeaderContains('content-type', 'application/json')
          .expectJSON({
            status: 'OK',
            payload: show.credits
          })
          .expectJSONTypes({
            time: String
          })
          .toss();

        frisby.create('The show should appear in the All Shows list')
          .get('http://localhost:7080/api/v2/show/allshows?api_key=travis-test-key')
          .expectStatus(200)
          .expectHeaderContains('content-type', 'application/json')
          .expectJSON('payload.?', {
            show_id: showid,
            title: show.title,
            description: show.description
          })
          .expectJSONTypes({
            time: String
          })
          .toss();
      })
      .toss();
  })
  .toss();
