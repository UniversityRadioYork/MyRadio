var frisby = require('frisby');
var url = require('./lib/url');

frisby.globalSetup({
  request: {
    headers: {'Accept': 'application/json'},
    inspectOnFailure: true
  }
});

frisby.create('Get public config')
  .get(url.base + 'config/publicconfig?api_key=travis-test-key')
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
