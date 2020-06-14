<?php

namespace Sourceml\Http\REST;

use Symfony\Component\HttpFoundation\Response;

class RestResponse extends Response {

    public function __construct($content = '', $status = 200, $headers = array()) {
        parent::__construct($content, $status, $headers);
        $this->headers->set('Content-Type', 'application/json; charset=utf-8');
    }

}
