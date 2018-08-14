<?php

namespace Symbiote\ApiWrapper;

class WebServiceException extends \Exception {
	public $status;

	public function __construct($status=403, $message='', $code=null, $previous=null) {
		$this->status = $status;
		parent::__construct($message, $code, $previous);
	}
}
