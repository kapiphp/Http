<?php

namespace Kapi\Http;

class ServerRequest extends AbstractServerRequest
{

    public function __construct($uri, $method, array $headers = [], $body = 'php://input', $version = '1.1')
    {
        parent::__construct($uri, $method, $headers, $body, $version);
    }


}