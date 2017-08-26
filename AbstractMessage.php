<?php

namespace Kapi\Http;

use InvalidArgumentException;
use Psr\Http\Message\MessageInterface;
use Psr\Http\Message\StreamInterface;

abstract class AbstractMessage implements MessageInterface
{
    /**
     * @var string
     */
    protected $protocol;

    /**
     * @var array
     */
    protected $headers;

    /**
     * @var array
     */
    protected $headerNames;

    /**
     * @var StreamInterface
     */
    protected $stream;

    /**
     * @inheritDoc
     */
    public function getProtocolVersion()
    {
        return $this->protocol;
    }

    /**
     * @inheritDoc
     */
    public function withProtocolVersion($version)
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

        $new = clone $this;
        $new->protocol = $version;

        return $new;
    }

    /**
     * @inheritDoc
     */
    public function getHeaders()
    {
        return $this->headers;
    }

    /**
     * @inheritDoc
     */
    public function hasHeader($name)
    {
        return isset($this->headerNames[strtolower($name)]);
    }

    /**
     * @inheritDoc
     */
    public function getHeader($name)
    {
        $name = strtolower($name);
        return isset($this->headerNames[$name]) ? $this->headers[$this->headerNames[$name]] : [];
    }

    /**
     * @inheritDoc
     */
    public function getHeaderLine($name)
    {
        return implode(', ', $this->getHeader($name));
    }

    /**
     * @inheritDoc
     */
    public function withHeader($name, $value)
    {
        if (!is_string($name)) {
            throw new InvalidArgumentException(sprintf(
                'Invalid header name type; expected string; received %s',
                is_object($name) ? get_class($name) : gettype($name)
            ));
        }

        $normalized = strtolower($name);

        $new = clone $this;
        if (isset($new->headerNames[$normalized])) {
            unset($new->headers[$new->headerNames[$normalized]]);
        }

        $new = $new->withAddedHeader($name, $value);

        return $new;
    }

    /**
     * @inheritDoc
     */
    public function withAddedHeader($name, $value)
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

        $new = clone $this;
        if (isset($new->headerNames[$normalized])) {
            $header = $this->headerNames[$normalized];
            $new->headers[$header] = array_merge($this->headers[$header], $value);
        } else {
            $new->headerNames[$normalized] = $name;
            $new->headers[$name] = $value;
        }

        return $new;
    }

    /**
     * @inheritDoc
     */
    public function withoutHeader($name)
    {
        $normalized = strtolower($name);

        if (!isset($this->headerNames[$normalized])) {
            return clone $this;
        }

        $name = $this->headerNames[$normalized];

        $new = clone $this;
        unset($new->headers[$name], $new->headerNames[$normalized]);

        return $new;
    }

    /**
     * @inheritDoc
     */
    public function getBody()
    {
        return $this->stream;
    }

    /**
     * @inheritDoc
     */
    public function withBody(StreamInterface $body)
    {
        $new = clone $this;
        $new->stream = $body;

        return $new;
    }
}