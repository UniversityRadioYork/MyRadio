const DEFAULT_PORT = 80;
module.exports = {
    get base() {
        return 'http://localhost:' + (process.env.PORT || DEFAULT_PORT) + '/api/v2/';
    }
}
