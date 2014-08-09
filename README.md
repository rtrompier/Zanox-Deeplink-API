Zanox-Deeplink-API
==================

Zanox API Deeplink generator

Until the release of the new Zanox API, here is a small script to generate programmatically a Zanox Deeplink.

Version
----

1.0

Pre-requisites
-----------

To use this code you need : 

* [Zanox Account] - Have an Zanox valid account
* [Zanox Adspace] - Create an Zanox Adspace
* [Zanox Partners] - Have an affiliate programme

Configuration
--------------

```php

$zanoxDeepLink = new ZanoxDeepLink('LOGIN', 'PASSWORD', 'ADSPACE', 'ADVERTISER');
echo $zanoxDeepLink->getDeeplink('PRODUCT_URL');

```

ADVERTISER is an optional parameter. Use it only if you have multiple program available for your URL.

![Screen of Zanox Deeplink Generator](https://raw.githubusercontent.com/rtrompier/Zanox-Deeplink-API/master/install/screen.png )

License
----

MIT

