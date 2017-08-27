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
     * @inheritDoc
     */
    public function withMethod($method)
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

        return parent::withMethod($method);
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

    /**
     * @param array|string $body
     * @return static
     */
    public function setBody($body)
    {
        if (is_array($body)) {
            $body = http_build_query($body);
        }

        $stream = new Stream('php://memory', 'wb+');
        $stream->write($body);

        return $this->withBody($stream);
    }
}