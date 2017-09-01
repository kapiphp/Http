<?php

namespace Kapi\Http;

use InvalidArgumentException;
use Psr\Http\Message\StreamInterface;

class Message extends AbstractMessage
{
    /**
     * Message constructor.
     *
     * @param array $headers
     * @param StreamInterface|null $body
     * @param string $version
     */
    public function __construct(array $headers = [], $body = 'php://memory', $version = '1.1')
    {
        $this->headers = $this->headerNames = [];
        $this->addHeaders($headers);
        $this->setProtocolVersion($version);
        $this->setBody($body);
    }

    /**
     * @param string $version
     */
    public function setProtocolVersion($version)
    {
        if (!is_string($version)) {
            throw new InvalidArgumentException(sprintf(
                'Unsupported HTTP protocol version; must be a string, received %s',
                (is_object($version) ? get_class($version) : gettype($version))
            ));
        }

        if (!$version) {
            throw new InvalidArgumentException('HTTP protocol version can not be empty');
        }

        if (!preg_match('/^(1\.[01]|2)$/', $version)) {
            throw new InvalidArgumentException(sprintf(
                'Unsupported HTTP protocol version "%s" provided',
                $version
            ));
        }

        $this->protocolVersion = $version;
    }

    /**
     * @param array $headers
     */
    public function setHeaders(array $headers)
    {
        foreach ($headers as $name => $value) {
            $this->setHeader($name, $value);
        }
    }

    /**
     * @param array $headers
     */
    public function addHeaders(array $headers)
    {
        foreach ($headers as $name => $value) {
            $this->addHeader($name, $value);
        }
    }

    /**
     * @param string $name
     * @param mixed $value
     */
    public function setHeader($name, $value)
    {
        if (!is_string($name)) {
            throw new InvalidArgumentException(sprintf(
                'Invalid header name type; expected string; received %s',
                is_object($name) ? get_class($name) : gettype($name)
            ));
        }

        $normalized = strtolower($name);

        if (isset($this->headerNames[$normalized])) {
            unset($this->headers[$this->headerNames[$normalized]]);
        }

        $this->addHeader($name, $value);
    }

    /**
     * @param string $name
     * @param mixed $value
     */
    public function addHeader($name, $value)
    {
        if (!is_string($name)) {
            throw new InvalidArgumentException(sprintf(
                'Invalid header name type; expected string; received %s',
                is_object($name) ? get_class($name) : gettype($name)
            ));
        }

        if (!preg_match('/^[a-zA-Z0-9\'`#$%&*+.^_|~!-]+$/', $name)) {
            throw new InvalidArgumentException(sprintf(
                '"%s" is not valid header name',
                $name
            ));
        }

        if (!is_array($value)) {
            $value = [$value];
        }

        $value = array_map(function ($value)
        {
            if (!is_string($value) && !is_numeric($value)) {
                throw new InvalidArgumentException(sprintf(
                    'Invalid header value type; must be a string or numeric; received %s',
                    is_object($value) ? get_class($value) : gettype($value)
                ));
            }

            $value = (string) $value;

            if (preg_match("#(?:(?:(?<!\r)\n)|(?:\r(?!\n))|(?:\r\n(?![ \t])))#", $value) || preg_match('/[^\x09\x0a\x0d\x20-\x7E\x80-\xFE]/', $value)) {
                throw new InvalidArgumentException(sprintf(
                    '"%s" is not valid header value',
                    $value
                ));
            }

            return $value;
        }, $value);

        $normalized = strtolower($name);

        if (isset($this->headerNames[$normalized])) {
            $header = $this->headerNames[$normalized];
            $this->headers[$header] = array_merge($this->headers[$header], $value);
        } else {
            $this->headerNames[$normalized] = $name;
            $this->headers[$name] = $value;
        }
    }

    /**
     * @param mixed $body
     */
    public function setBody($body)
    {
        if (!$body instanceof StreamInterface) {
            $body = new Stream($body, 'wb+');
        }

        $this->stream = $body;
    }
}