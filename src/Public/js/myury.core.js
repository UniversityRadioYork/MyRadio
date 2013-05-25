window.myury = {
  makeURL: function (module, action) {
    if (mConfig.rewrite_url) return mConfig.base_url+module+'/'+action+'/';
    else return mConfig.base_url+'?module='+module+'&action='+action;
  }
};