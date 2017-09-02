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

        $targetDirectory = dirname($targetPath);
        if (! is_dir($targetDirectory) || ! is_writable($targetDirectory)) {
            throw new RuntimeException(sprintf(
                'The target directory `%s` does not exists or is not writable',
                $targetDirectory
            ));
        }

        $sapi = PHP_SAPI;
        if (!$this->file || empty($sapi) || 0 === strpos(PHP_SAPI, 'cli')) {
            // Non-SAPI environment, or no filename present
            $handle = fopen($targetPath, 'wb+');
            if (false === $handle) {
                throw new RuntimeException('Unable to write to designated path');
            }

            $stream = $this->getStream();
            $stream->rewind();
            while (! $stream->eof()) {
                fwrite($handle, $stream->read(4096));
            }

            fclose($handle);
        } else {
            // SAPI environment, with file present
            if (false === move_uploaded_file($this->file, $targetPath)) {
                throw new RuntimeException('Error occurred while moving uploaded file');
            }
        }

        $this->moved = true;


        if ($this->file) {
            $this->moved = php_sapi_name() == 'cli'
                ? rename($this->file, $targetPath)
                : move_uploaded_file($this->file, $targetPath);
        } else {
            $source = $this->getStream();
            $dest = new LazyOpenStream($targetPath, 'w');
            $maxLen = -1;

            $bufferSize = 8192;

            if ($maxLen === -1) {
                while (!$source->eof()) {
                    if (!$dest->write($source->read($bufferSize))) {
                        break;
                    }
                }
            } else {
                $remaining = $maxLen;
                while ($remaining > 0 && !$source->eof()) {
                    $buf = $source->read(min($bufferSize, $remaining));
                    $len = strlen($buf);
                    if (!$len) {
                        break;
                    }
                    $remaining -= $len;
                    $dest->write($buf);
                }
            }

            $this->moved = true;
        }

        if (false === $this->moved) {
            throw new RuntimeException(
                sprintf('Uploaded file could not be moved to %s', $targetPath)
            );
        }
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