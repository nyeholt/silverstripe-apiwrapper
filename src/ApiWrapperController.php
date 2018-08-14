<?php

namespace Symbiote\ApiWrapper;

use SilverStripe\Control\HTTPRequest;
use SilverStripe\Control\Controller;
use SilverStripe\Core\Injector\Injector;


class ApiWrapperController extends Controller
{
    private static $versions = [
        'v1' => [
            'docs'      => EndpointsController::class,
            'watch'     => ServiceWrapperController::class,
        ]
    ];

    public function handleRequest(HTTPRequest $request)
    {
        $apiVersions = self::config()->versions;
        foreach ($apiVersions as $version => $handlers) {
            $res = $request->match($version, true);
            if ($res) {
                foreach ($handlers as $segment => $cls) {
                    if ($request->match($segment, true)) {
                        $controller = is_string($cls) ? Injector::inst()->create($cls) : $cls;
                        if ($controller) {
                            if (method_exists($controller, 'setSegment')) {
                                $controller->setSegment($segment);
                            }
                            $response = $controller->handleRequest($request);
                            return $response;
                        }
                    }
                }
            }
        }

        return parent::handleRequest($request);
    }
}
