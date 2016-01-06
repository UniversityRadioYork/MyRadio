var frisby = require('frisby');
frisby.create('Get Brightbit Twitter feed')
  .get('https://api.twitter.com/1/statuses/user_timeline.json?screen_name=brightbit')
  .expectStatus(200)
  .expectHeaderContains('content-type', 'application/json')
  .expectJSON('0', {
    place: function(val) { expect(val).toMatchOrBeNull("Oklahoma City, OK"); }, // Custom matcher callback
    user: {
      verified: false,
      location: "Oklahoma City, OK",
      url: "http://brightb.it"
    }
  })
  .expectJSONTypes('0', {
    id_str: String,
    retweeted: Boolean,
    in_reply_to_screen_name: function(val) { expect(val).toBeTypeOrNull(String); }, // Custom matcher callback
    user: {
      verified: Boolean,
      location: String,
      url: String
    }
  })
.toss();

frisby.create('Get public config')
  .get('http://localhost/api/v2/config/publicconfig?api_key=travis-test-key')
  .expectStatus(200)
  .expectHeaderContains('content-type', 'application/json')
  .expectJSON({
    status: 'OK',
    'payload': {
      'short_name': 'URN'
    }
  })
  .expectJSONTypes({
    id_str: String,
    retweeted: Boolean,
    in_reply_to_screen_name: function(val) { expect(val).toBeTypeOrNull(String); }, // Custom matcher callback
    user: {
      verified: Boolean,
      location: String,
      url: String
    }
  })
.toss();
