<?php

namespace Kapi\Http;

class ServerRequest extends AbstractServerRequest
{
    /**
     * ServerRequest constructor.
     * @param array $serverParams
     * @param string $uri
     * @param string $method
     * @param array $headers
     * @param string $body
     * @param string $version
     */
    public function __construct(array $serverParams = [], $uri, $method, array $headers = [], $body = 'php://input', $version = '1.1')
    {
        $this->serverParams = $serverParams;

        parent::__construct($uri, $method, $headers, $body, $version);
    }

    /**
     * @param mixed $body
     */
    public function setBody($body)
    {
        if ('php://input' === $body) {
            $body = new Stream($body, 'r');
        }

        parent::setBody($body);
    }
}