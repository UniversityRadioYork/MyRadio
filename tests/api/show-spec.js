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
  email: 'showspecdevnull@example.com',
  receive_email: false
};

var show = {
  title: 'Travis likes to test shows.',
  description: 'Travis is a busy guy, but in his spare time, he does his own radio show to test what this on the air malarkey is all about.',
};

frisby.create('Create a test member')
  .post('http://localhost/api/v2/user?api_key=travis-test-key', user, {json: true})
  .expectStatus(201)
  .afterJSON(function(json) {
    var memberid = json.payload.memberid;
    show.credits = [{type: 1, memberid: memberid}];

    frisby.create('Create a test show')
      .post('http://localhost/api/v2/show?api_key=travis-test-key', show, {json: true})
      .expectStatus(201)
      .expectHeaderContains('content-type', 'application/json')
      .expectJSON({
        status: 'OK',
        payload: {
          title: show.title,
          description: show.description,
          seasons: {
            value: 0
          }
        }
      })
      .expectJSONTypes({
        time: String,
        show_id: Number,
        // Don't like, but backwards compatibility :(
        credits: String
      })
      .toss();
  })
  .toss();
