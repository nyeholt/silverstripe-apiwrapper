<?php

namespace Symbiote\ApiWrapper;

trait WrappedApi
{

    protected $segment;

    public function setSegment($s) {
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

    public function validateJson($json, $model)
    {
        foreach ($model as $i) {
            if (!isset($json[$i])) {
                return false;
            }
            if(getype($json[$i]) == "array") {
                if(!validateJson($json[$i], $model[$i])) {
                    return false;
                }
            }
        }
        return true;
    }

    public function sendResponse($payload, $message = "success", $status = 200)
    {
        $this->getResponse()->setBody(json_encode([
            "status" => $status,
            "message" => $message,
            "payload" => $payload
        ]));

        $this->getResponse()->addHeader("Content-type", "application/json");
        return $this->getResponse();
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
