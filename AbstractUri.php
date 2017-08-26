<?php

namespace Kapi\Http;

use InvalidArgumentException;
use Psr\Http\Message\UriInterface;

abstract class AbstractUri implements UriInterface
{
    public const DEFAULT_PORT = [
        'http' => 80,
        'https' => 443,
        'smtp' => 25,
        'imap' => 143,
        'pop' => 110,
        'ftp' => 21,
        'ftps' => 990,
        'sftp' => 22,
        'ssh' => 22,
        'telnet' => 23,
        'snmp' => 161,
        'dns' => 53,
        'gopher' => 70,
        'ldap' => 389,
        'ldaps' => 636,
        'sip' => 5060,
        'sips' => 5061,
        'nntp' => 119,
        'news' => 119,
        'nntps' => 563,
    ];

    /**
     * @var string
     */
    protected $scheme;

    /**
     * @var string
     */
    protected $userInfo;

    /**
     * @var string
     */
    protected $host;

    /**
     * @var int
     */
    protected $port;

    /**
     * @var string
     */
    protected $path;

    /**
     * @var string
     */
    protected $query;

    /**
     * @var string
     */
    protected $fragment;

    /**
     * @inheritDoc
     */
    public function getScheme()
    {
        return $this->scheme;
    }

    /**
     * @inheritDoc
     */
    public function getAuthority()
    {
        if ($authority = $this->host) {
            if ($this->userInfo) {
                $authority = $this->userInfo . '@' . $authority;
            }

            if ($this->getPort()) {
                $authority .= ':' . $this->port;
            }
        }

        return $authority;
    }

    /**
     * @inheritDoc
     */
    public function getUserInfo()
    {
        return $this->userInfo;
    }

    /**
     * @inheritDoc
     */
    public function getHost()
    {
        return $this->host;
    }

    /**
     * @inheritDoc
     */
    public function getPort()
    {
        return
            $this->port
            && (!$this->scheme
                || !isset(self::DEFAULT_PORT[$this->scheme])
                || $this->port !== self::DEFAULT_PORT[$this->scheme])
            ? $this->port
            : null;
    }

    /**
     * @inheritDoc
     */
    public function getPath()
    {
        return $this->path;
    }

    /**
     * @inheritDoc
     */
    public function getQuery()
    {
        return $this->query;
    }

    /**
     * @inheritDoc
     */
    public function getFragment()
    {
        return $this->fragment;
    }

    /**
     * @inheritDoc
     */
    abstract public function withScheme($scheme);

    /**
     * @inheritDoc
     */
    abstract public function withUserInfo($user, $password = null);

    /**
     * @inheritDoc
     */
    abstract public function withHost($host);

    /**
     * @inheritDoc
     */
    public function withPort($port)
    {
        if (null !== $port) {
            if (!is_int($port)) {
                throw new InvalidArgumentException(sprintf(
                    'Invalid port "%s" specified; must be an integer, an integer string, or null',
                    is_object($port) ? get_class($port) : gettype($port)
                ));
            }

            if ($port < 1 || $port > 65535) {
                throw new InvalidArgumentException(sprintf(
                    'Invalid port "%d" specified; must be a valid TCP/UDP port',
                    $port
                ));
            }
        }

        $new = clone $this;
        $new->port = $port;

        return $new;
    }

    /**
     * @inheritDoc
     */
    abstract public function withPath($path);

    /**
     * @inheritDoc
     */
    abstract public function withQuery($query);

    /**
     * @inheritDoc
     */
    abstract public function withFragment($fragment);

    /**
     * @inheritDoc
     */
    public function __toString()
    {
        $uri = '';

        if ($this->scheme) {
            $uri .= $this->scheme . ':';
        }

        if ($authority = $this->getAuthority()) {
            $uri .= '//' . $authority;
        }

        if ($path = $this->path) {
            if ($authority && $path[0] !== '/') {
                $path = '/' . $path;
            }

            if (!$authority && 0 === strpos($this->path, '//')) {
                $path = '/' . ltrim($this->path, '/');
            }

            $uri .= $path;
        }

        if ($this->query) {
            $uri .= '?' . $this->query;
        }

        if ($this->fragment) {
            $uri .= '#' . $this->fragment;
        }

        return $uri;
    }
}