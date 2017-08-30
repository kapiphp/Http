<?php

namespace Kapi\Http;

use Psr\Http\Message\StreamInterface;
use Psr\Http\Message\UploadedFileInterface;

abstract class AbstractUploadedFile implements UploadedFileInterface
{
    /**
     * @var StreamInterface
     */
    protected $stream;

    /**
     * @var
     */
    protected $file;

    protected $size;

    protected $error;

    /**
     * @var string
     */
    protected $clientFileName;

    /**
     * @var string
     */
    protected $clientMediaType;

    /**
     * @inheritDoc
     */
    public function getStream()
    {
        // TODO: Implement getStream() method.

        if ($this->stream instanceof StreamInterface) {
            return $this->stream;
        }

        $this->stream = new Stream($this->file);
        return $this->stream;
    }

    /**
     * @inheritDoc
     */
    public function moveTo($targetPath)
    {
        // TODO: Implement moveTo() method.
    }

    /**
     * @inheritDoc
     */
    public function getSize()
    {
        return $this->size;
    }

    /**
     * @inheritDoc
     */
    public function getError()
    {
        return $this->error;
    }

    /**
     * @inheritDoc
     */
    public function getClientFilename()
    {
        return $this->clientFileName;
    }

    /**
     * @inheritDoc
     */
    public function getClientMediaType()
    {
        return $this->clientMediaType;
    }

}