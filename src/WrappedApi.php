<?php

namespace Symbiote\ApiWrapper;

trait WrappedApi
{

    protected $segment;

    public function setSegment($s)
    {
        $this->segment = $s;
    }

    public function getJson($request)
    {
        $json = $request->getBody();
        if (!isset($json)) {
            $json = json_decode($json);
        }
        return $json;
    }

    protected function sendRawResponse($body)
    {
        $this->getResponse()->setBody($body);
        $this->getResponse()->addHeader("Content-type", "application/json");
        return $this->getResponse();
    }

    public function sendResponse($payload, $message = "success", $status = 200)
    {
        return $this->sendRawResponse(json_encode([
            "status" => $status,
            "message" => $message,
            "payload" => $payload
        ]));
    }

    public function sendError($message)
    {
        return $this->sendResponse(
            [],
            $message,
            "failure"
        );
    }
}
