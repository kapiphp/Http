<?php

namespace Kapi\Http;

use Psr\Http\Message\StreamInterface;
use RuntimeException;

abstract class AbstractStream implements StreamInterface
{
    /**
     * @var resource|null
     */
    protected $resource;

    /**
     * @inheritDoc
     */
    public function __toString()
    {
        try {
            $this->rewind();
            return $this->getContents();
        } catch (RuntimeException $e) {
            return '';
        }
    }

    /**
     * @inheritDoc
     */
    public function close()
    {
        if ($this->resource) {
            fclose($this->detach());
        }
    }

    /**
     * @inheritDoc
     */
    public function detach()
    {
        $resource = $this->resource;
        $this->resource = null;
        return $resource;
    }

    /**
     * @inheritDoc
     */
    public function getSize()
    {
        if (!$this->resource) {
            return null;
        }

        $stats = fstat($this->resource);
        return $stats['size'];
    }

    /**
     * @inheritDoc
     */
    public function tell()
    {
        if (!$this->resource) {
            throw new RuntimeException('No resource available; cannot tell position');
        }

        $result = ftell($this->resource);
        if (!is_int($result)) {
            throw new RuntimeException('Unable to determine stream position');
        }

        return $result;
    }

    /**
     * @inheritDoc
     */
    public function eof()
    {
        return !$this->resource || feof($this->resource);
    }

    /**
     * @inheritDoc
     */
    public function isSeekable()
    {
        return $this->getMetadata('seekable');
    }

    /**
     * @inheritDoc
     */
    public function seek($offset, $whence = SEEK_SET)
    {
        if (!$this->isSeekable()) {
            throw new RuntimeException('Stream is not seekable');
        }

        if (-1 === fseek($this->resource, $offset, $whence)) {
            throw new RuntimeException('Failure seeking within stream');
        }
    }

    /**
     * @inheritDoc
     */
    public function rewind()
    {
        $this->seek(0);
    }

    /**
     * @inheritDoc
     */
    public function isWritable()
    {
        $mode = $this->getMetadata('mode');
        return strstr($mode, 'x') || strstr($mode, 'w') || strstr($mode, 'c') || strstr($mode, 'a') || strstr($mode, '+');
    }

    /**
     * @inheritDoc
     */
    public function write($string)
    {
        if (!$this->isWritable()) {
            throw new RuntimeException('Stream is not writable');
        }

        $result = fwrite($this->resource, $string);
        if (false === $result) {
            throw new RuntimeException('Failure writing to stream');
        }

        return $result;
    }

    /**
     * @inheritDoc
     */
    public function isReadable()
    {
        $mode = $this->getMetadata('mode');
        return strstr($mode, 'r') || strstr($mode, '+');
    }

    /**
     * @inheritDoc
     */
    public function read($length)
    {
        if (!$this->isReadable()) {
            throw new RuntimeException('Stream is not readable');
        }

        $read = fread($this->resource, $length);
        if (false === $read) {
            throw new RuntimeException('Failure reading stream');
        }

        return $read;
    }

    /**
     * @inheritDoc
     */
    public function getContents()
    {
        if (!$this->isReadable()) {
            throw new RuntimeException('Stream is not readable');
        }

        $contents = stream_get_contents($this->resource);
        if (false === $contents) {
            throw new RuntimeException('Failure reading stream contents');
        }

        return $contents;
    }

    /**
     * @inheritDoc
     */
    public function getMetadata($key = null)
    {
        if (!$this->resource) {
            throw new RuntimeException('No resource available; Cannot get meta data');
        }

        $metadata = stream_get_meta_data($this->resource);

        if (null === $key) {
            return $metadata;
        }

        return $metadata[$key] ?? null;
    }
}
