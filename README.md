# [Module Title]

[![Latest Stable Version](https://poser.pugx.org/symbiote/silverstripe-apiwrapper/version.svg)](https://github.com/symbiote/silverstripe-apiwrapper/releases)
[![Latest Unstable Version](https://poser.pugx.org/symbiote/silverstripe-apiwrapper/v/unstable.svg)](https://packagist.org/packages/symbiote/silverstripe-apiwrapper)
[![Total Downloads](https://poser.pugx.org/symbiote/silverstripe-apiwrapper/downloads.svg)](https://packagist.org/packages/symbiote/silverstripe-apiwrapper)
[![License](https://poser.pugx.org/symbiote/silverstripe-apiwrapper/license.svg)](https://github.com/symbiote/silverstripe-apiwrapper/blob/master/LICENSE.md)


Wrap your service APIs in a web layer

## Composer Install

```
composer require symbiote/silverstripe-apiwrapper:~1.0
```

## Requirements

* SilverStripe 4.1+

## Documentation

**Quick start**

Suppose we have a class `Symbiote\Watch\WatchService` that we want to expose
to web requests via /api/v1/watch/{methodname}

First, implement `webEnabledMethods`, returning an array of methods mapping to
the request types that can trigger them, ie

```
    public function webEnabledMethods()
    {
        return [
            'store' => 'POST',
            'list' => 'GET',
        ];
    }

```

If you want some methods to be publicly accessible, return a map of those

```
    public function publicWebMethods()
    {
        return [
            'list' => true,
        ];
    }

```


In config;

```
---
Name: my_webservices
---
Symbiote\ApiWrapper\ApiWrapperController:
  versions:
    v1:
      watch: 'WatchServiceApiController' # The name of an injector service

SilverStripe\Core\Injector\Injector:
  WatchServiceApiController: # Referenced by above
    class: Symbiote\ApiWrapper\ServiceWrapperController
    properties:
      service: %$Symbiote\Watch\WatchService
```

* [Advanced Usage](docs/en/advanced-usage.md)
* [License](LICENSE.md)
* [Contributing](CONTRIBUTING.md)

## Credits (OPTIONAL)

Mention dependencies / shoutouts / stackoverflow answers that assisted.

ie.
* [Jonom](https://github.com/jonom/silverstripe-environment-awareness) for the format of this README.md
* [Barakat S](https://github.com/FileZ/php-clamd) for clamd PHP interface
* ["How to Forge" users](https://web.archive.org/web/20161124000346/https://www.howtoforge.com/community/threads/clamd-will-not-start.34559/) for fixing permissions relating to ClamAV
