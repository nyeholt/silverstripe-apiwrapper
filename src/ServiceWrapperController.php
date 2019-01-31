<?php

namespace Symbiote\ApiWrapper;

use SilverStripe\Control\Controller;
use SilverStripe\Control\HTTPRequest;
use SilverStripe\Control\HTTP;
use SilverStripe\Control\HTTPResponse;
use SilverStripe\Security\Member;
use SilverStripe\Core\Convert;
use SilverStripe\Control\HTTPResponse_Exception;
use SilverStripe\ORM\DataObject;
use SilverStripe\ORM\DataList;
use SilverStripe\ORM\SS_List;
use SilverStripe\Security\Permission;

class ServiceWrapperController extends Controller
{

    use WrappedApi;

    protected $format = 'json';

    /**
     * The service that this controller wraps around
     */
    public $service;

    public function handleRequest(HTTPRequest $request)
    {
        try {
            $this->beforeHandleRequest($request);
            $this->setRequest($request);

			// borrowed from Controller
            $this->urlParams = $request->allParams();
            $this->request = $request;
            $this->response = new HTTPResponse();

            $this->extend('onBeforeInit');
            $this->init();
            $this->extend('onAfterInit');

            if ($this->response->isFinished()) {
                return $this->response;
            }

            $response = $this->handleService($request);

            if ($response instanceof HTTPResponse) {
                $response->addHeader('Content-Type', 'application/' . $this->format);
            }
            HTTP::add_cache_headers($this->response);

            $this->afterHandleRequest();

            return $response;
        } catch (WebServiceException $exception) {
            $this->response = new HTTPResponse();
            $this->response->setStatusCode($exception->status);
            return $this->sendError($exception->getMessage(), $exception->status);
			// $this->response->setBody();
        } catch (HTTPResponse_Exception $e) {
            $this->response = $e->getResponse();
            return $this->sendError($e->getMessage(), $e->getCode());
        } catch (\Exception $exception) {
            $code = 500;
			// check type explicitly in case the Restricted Objects module isn't installed
            if (class_exists(PermissionDeniedException::class) && $exception instanceof PermissionDeniedException) {
                $code = 403;
            }
            $this->response = new HTTPResponse();
            $this->response->setStatusCode($code);
            return $this->sendError($exception->getMessage(), $code);
        }

        return $this->response;
    }

    public function handleService(HTTPRequest $request)
    {
        $service = ucfirst($this->segment) . 'Service';
        $method = $request->shift();
        $body = $request->getBody();
        $requestType = strlen($body) > 0 ? 'POST' : $request->httpMethod(); // (count($request->postVars()) > 0 ? 'POST' : 'GET');

        $svc = $this->service ? : Injector::inst()->get($service);

        $response = '';

        if ($svc && method_exists($svc, 'webEnabledMethods')) {
            $allowedMethods = array();
            if (method_exists($svc, 'webEnabledMethods')) {
                $allowedMethods = $svc->webEnabledMethods();
            }

			// if we have a list of methods, lets use those to restrict
            if (count($allowedMethods)) {
                $this->checkMethods($method, $allowedMethods, $requestType);
            } else {
				// we only allow 'read only' requests so we wrap everything
				// in a readonly transaction so that any database request
				// disallows write() calls
				// @TODO
            }

            if (!Member::currentUserID()) {
				// require service to explicitly state that the method is allowed
                if (method_exists($svc, 'publicWebMethods')) {
                    $publicMethods = $svc->publicWebMethods();
                    if (!isset($publicMethods[$method])) {
                        throw new WebServiceException(403, "Public method $method not allowed");
                    }
                } else {
                    throw new WebServiceException(403, "Method $method not allowed; no public methods defined");
                }
            }

            $refObj = new \ReflectionObject($svc);
            $refMeth = $refObj->getMethod($method);
			/* @var $refMeth ReflectionMethod */
            if ($refMeth) {
                $allArgs = $this->getRequestArgs($request, $requestType);
                $params = $this->mapMethodToParameters($refMeth, $allArgs);

                $return = $refMeth->invokeArgs($svc, $params);

                if ($return instanceof SS_List) {
                    $return = $return->toNestedArray();
                } else if ($return instanceof DataObject) {
                    $return = $return->toMap();
                }

                return $this->sendResponse($return);
            }
        }

        return $this->sendError('Invalid request', 400);
    }

    /**
     * Maps a given method's parameters against the provided arguments from
     * the request to ensure we have values for the subsequent call
     * we're going to make to said method
     *
     * Some special cases;
     *
     * * For method parameters of type DataObject or descendents, supply
     *   arguments of the form paramID and paramClass, where paramClass is
     *   the SilverStripe _table\_name_ property. This loads the dataobject
     *   into the parameter array
     * * To support file uploads, have a method with a single param named
     *   $file, and set the POST body to be the file content
     *
     *
     * @param \ReflectionMethod $method
     *              The method that will be called
     * @param array $allArgs
     *              All the arguments found in the request
     */
    public function mapMethodToParameters(\ReflectionMethod $method, $allArgs)
    {
        $params = [];
        $refParams = $method->getParameters();

        foreach ($refParams as $refParm) {
            /* @var $refParm ReflectionParameter */
            $paramClass = $refParm->getClass();
            // if we're after a dataobject, we'll try and find one using
            // this name with ID and Type parameters
            if ($paramClass && ($paramClass->getName() == DataObject::class || $paramClass->isSubclassOf(DataObject::class))) {
                $idArg = $refParm->getName() . 'ID';
                $typeArg = $refParm->getName() . 'Class';

                // look up the actual class type
                $type = DataObject::getSchema()->tableClass($allArgs[$typeArg]);

                if (isset($allArgs[$idArg]) && $type && class_exists($type)) {
                    $object = $type::get()->byId($allArgs[$idArg]);
                    if ($object && $object->canView()) {
                        $params[$refParm->getName()] = $object;
                    }
                } else {
                    $params[$refParm->getName()] = null;
                }
            } else if (isset($allArgs[$refParm->getName()])) {
                $params[$refParm->getName()] = $allArgs[$refParm->getName()];
            } else if ($refParm->getName() == 'file' && $requestType == 'POST') {
                // special case of a binary file upload
                $params['file'] = $body;
            } else if ($refParm->isOptional()) {
                $params[$refParm->getName()] = $refParm->getDefaultValue();
            } else {
                throw new WebServiceException(500, "Service method $method expects parameter " . $refParm->getName());
            }
        }
        return $params;
    }

    /**
     * Process a request URL + body to get all parameters for a request
     *
     * @param string $requestType
     * @return array
     */
    public function getRequestArgs(HTTPRequest $request, $requestType = 'GET')
    {
        if ($requestType == 'GET') {
            $allArgs = $request->getVars();
        } else {
            $allArgs = $request->postVars();
        }

        unset($allArgs['url']);

        $contentType = strtolower($request->getHeader('Content-Type'));

        if (strpos($contentType, 'application/json') !== false && !count($allArgs) && strlen($request->getBody())) {
			// decode the body to a params array
            $bodyParams = Convert::json2array($request->getBody());
            if (isset($bodyParams['params'])) {
                $allArgs = $bodyParams['params'];
            } else {
                $allArgs = $bodyParams;
            }
        }

		// see if there's any other URL bits to chew up
        $remaining = $request->remaining();
        $bits = explode('/', $remaining);

        for ($i = 0, $c = count($bits); $i < $c; ) {
            $key = $bits[$i];
            $val = isset($bits[$i + 1]) ? $bits[$i + 1] : null;
            if ($val && !isset($allArgs[$key])) {
                $allArgs[urldecode($key)] = urldecode($val);
            }
            $i += 2;
        }

        return $allArgs;
    }


    protected function checkMethods($method, $allowedMethods, $requestType)
    {
        if (!isset($allowedMethods[$method])) {
            throw new WebServiceException(403, "You do not have permission to $method");
        }

        $info = $allowedMethods[$method];
        $allowedType = $info;
        if (is_array($info)) {
            $allowedType = isset($info['type']) ? $info['type'] : '';

            if (isset($info['perm'])) {
                if (!Permission::check($info['perm'])) {
                    throw new WebServiceException(403, "You do not have permission to $method");
                }
            }
        }

		// otherwise it might be the wrong request type
        if ($requestType != $allowedType) {
            throw new WebServiceException(405, "$method does not support $requestType");
        }
    }
}
