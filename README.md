# SilverStripe API Wrapper

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

## Additional Options

The return of webEnabledMethods can provide additional information, such as

```

return [
  'list' => [
    'type' => 'GET', 
    'call' => 'myMethod', // the name of a method on the service to execute 
    'public' => true,
    'match' => 'regex; see below',
    'perm' => 'CMS_Access_etc'
  ]
]
```

**match**

You can define a regex in the 'match' key which allows you to match parameters
from the URL substring that is left after the api and method paths are removed. 

Note that you _must_ define regex using named capture groups. 

For example

`'match' => '(?<parent>\d+)'`

and the URL 

`/api/v1/list/12`

will call the 'list' method with the `$parent` parameter set to 12. 

Note that this must be the _full_ URL match, meaning you should have a capture
group for all parts of the URL. In other words, the 'match' string is wrapped
in `"{^" . $match . "$}"` for the regex that actually gets executed. 


* [License](LICENSE.md)
* [Contributing](CONTRIBUTING.md)
