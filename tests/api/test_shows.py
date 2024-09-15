from playwright.sync_api import APIRequestContext

def test_allshows(api_v2: APIRequestContext):
    res = api_v2.get("show/allshows")
    assert res.status == 200

