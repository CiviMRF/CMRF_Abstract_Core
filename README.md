You can find the general documentation at

### The CurlAuthX connector
In the latest versions of CiviCRM, the endpoint `https://my-civi.org/sites/all/modules/civicrm/extern/rest.php` is not supported anymore. It is replaced by a new extension `AuthX` that provides a lot of different authentication options. The CurlAuthX connector uses just one of them.

The AuthX does not have a graphical interface (yet). You can configure it with the `cv` utility.

* enable the new authx extension `cv ext:enable authx`
* choose the authentication method `cv ev 'Civi::settings()->set("authx_xheader_cred",["api_key"]);'`
* make sitekey mandatory `cv ev 'Civi::settings()->set("authx_guards",["site_key"]);'`
* refresh the cache `cv flush`

