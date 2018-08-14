<?php

namespace Symbiote\ApiWrapper;

use SilverStripe\Control\Controller;

class EndpointsController extends Controller
{
    use WrappedApi;

    private static $allowed_actions = [
        'list'
    ];

    public function list() {
        return $this->sendResponse(['items' => ['TODO']]);
    }
}
