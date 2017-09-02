<?php

namespace Kapi\Http;

use InvalidArgumentException;
use Psr\Http\Message\UriInterface;

class Request extends AbstractRequest
{
    /**
     * @var string[]
     */
    protected $allowedMethod = [
        'GET',
        'POST',
        'PUT',
        'PATCH',
        'DELETE',
        'HEAD',
        'OPTIONS',
        'TRACE',
    ];

    /**
     * Request constructor.
     * @param null|string|UriInterface $uri
     * @param string $method
     * @param array $headers
     * @param $body
     * @param string $version
     */
    public function __construct($uri, $method, array $headers = [], $body = 'php://memory', $version = '1.1')
    {
        parent::__construct($headers, $body, $version);
        $this->setUri($uri);
        $this->setMethod($method);
    }

    /**
     * @inheritDoc
     */
    public function withMethod($method)
    {
        $new = clone $this;
        $new->setMethod($method);

        return $new;
    }

    /**
     * @param string $method
     */
    public function setMethod($method)
    {
        if (!is_string($method)) {
            throw new InvalidArgumentException(sprintf(
                'Unsupported HTTP method; must be a string, received %s',
                is_object($method) ? get_class($method) : gettype($method)
            ));
        }

        if (!in_array($method, $this->allowedMethod)) {
            throw new InvalidArgumentException(sprintf(
                'Unsupported HTTP method "%s" provided',
                $method
            ));
        }

        $this->method = $method;
    }

    /**
     * @param null|string|UriInterface $uri
     * @return static
     */
    public function setUri($uri)
    {
        $uri = new Uri((string) $uri);
        return $this->withUri($uri);
    }
}