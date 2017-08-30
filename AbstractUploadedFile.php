<?php

namespace Kapi\Http;

use InvalidArgumentException;
use Psr\Http\Message\StreamInterface;
use Psr\Http\Message\UploadedFileInterface;
use RuntimeException;

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

    protected $moved;

    protected $size;

    protected $error;

    /**
     * @var string
     */
    protected $clientFilename;

    /**
     * @var string
     */
    protected $clientMediaType;

    /**
     * @inheritDoc
     */
    public function getStream()
    {
        if ($this->error !== UPLOAD_ERR_OK) {
            throw new RuntimeException('Cannot retrieve stream due to upload error');
        }

        if ($this->moved) {
            throw new RuntimeException('Cannot retrieve stream after it has already been moved');
        }

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
        if ($this->error !== UPLOAD_ERR_OK) {
            throw new RuntimeException('Cannot retrieve stream due to upload error');
        }

        if ($this->moved) {
            throw new RuntimeException('Cannot move file; already moved!');
        }

        if (!is_string($targetPath) || !$targetPath) {
            throw new InvalidArgumentException(
                'Invalid path provided for move operation; must be a non-empty string'
            );
        }
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
        return $this->clientFilename;
    }

    /**
     * @inheritDoc
     */
    public function getClientMediaType()
    {
        return $this->clientMediaType;
    }

}