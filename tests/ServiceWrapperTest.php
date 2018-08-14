<?php

namespace Symbiote\ApiWrapper\Tests;

use SilverStripe\Dev\SapphireTest;
use SilverStripe\Control\HTTPRequest;
use Symbiote\ApiWrapper\ServiceWrapperController;
use SilverStripe\Core\Injector\Injector;
use SilverStripe\ORM\DataObject;
use SilverStripe\Dev\TestOnly;

class ServiceWrapperTest extends SapphireTest
{
    protected static $extra_dataobjects = [
        ServiceWrapTestObject::class
    ];

    public function testGetRequestArguments()
    {

        $request = new HTTPRequest('GET', 'param1/value1/param2/value2');

        /** @var ServiceWrapperController $controller */
        $controller = Injector::inst()->create(ServiceWrapperController::class);

        $args = $controller->getRequestArgs($request);

        $this->assertTrue(is_array($args));

        $this->assertEquals('value1', $args['param1']);
        $this->assertEquals('value2', $args['param2']);

        $request->setBody('{"bodyarg1": "bodyval1", "bodyarg2": "bodyval2"}');
        $request->addHeader('Content-Type', 'application/json');

        $args = $controller->getRequestArgs($request);

        $this->assertTrue(is_array($args));

        $this->assertEquals('bodyval1', $args['bodyarg1']);
        $this->assertEquals('bodyval2', $args['bodyarg2']);
    }

    public function testMapMethodArguments()
    {

        $obj = new ServiceWrapTestObject([
            'Title' => 'This is a page',
        ]);

        $obj->write();

        $controller = Injector::inst()->create(ServiceWrapperController::class);

        $requestVars = [
            'objectID' => $obj->ID,
            'objectClass' => 'ServiceWrapTestObject',
            'other' => 'value',
        ];

        $method = new \ReflectionMethod($this, 'methodForTesting');
        $params = $controller->mapMethodToParameters($method, $requestVars);

        $this->assertEquals($obj->Title, $params['object']->Title);
        $this->assertEquals('value', $params['other']);
    }

    public function methodForTesting(DataObject $object, $other)
    {

    }
}


class ServiceWrapTestObject extends DataObject implements TestOnly
{
    private static $table_name = 'ServiceWrapTestObject';

    private static $db = [
        'Title' => 'Varchar(128)',
    ];

    public function canView($member = null)
    {
        return true;
    }
}
