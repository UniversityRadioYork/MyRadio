var frisby = require('frisby');

frisby.globalSetup({
  request: {
    headers: {'Accept': 'application/json'},
    inspectOnFailure: true
  }
});

frisby.create('Get public config')
  .get('http://localhost/api/v2/config/publicconfig?api_key=travis-test-key')
  .expectStatus(200)
  .expectHeaderContains('content-type', 'application/json')
  .expectJSON({
    status: 'OK',
    payload: {
      short_name: 'URN'
    }
  })
  .expectJSONTypes({
    time: String
  })
.toss();
