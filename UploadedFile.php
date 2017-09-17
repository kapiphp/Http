<?php

namespace Kapi\Http;

use InvalidArgumentException;
use Psr\Http\Message\StreamInterface;

class UploadedFile extends AbstractUploadedFile
{
    /**
     * @param string|resource|StreamInterface $file
     * @param int $size
     * @param int $error
     * @param string|null $clientFilename
     * @param string|null $clientMediaType
     * @throws InvalidArgumentException
     */
    public function __construct($file, $size, $error, $clientFilename = null, $clientMediaType = null)
    {
        if (!is_int($error) || 0 > $error || 8 < $error) {
            throw new InvalidArgumentException(
                'Upload file error status must be an UPLOAD_ERR_* constant'
            );
        }
        $this->error = $error;

        if ($error === UPLOAD_ERR_OK) {
            if (is_string($file)) {
                $this->file = $file;
            } elseif (is_resource($file)) {
                $this->stream = new Stream($file);
            } elseif ($file instanceof StreamInterface) {
                $this->stream = $file;
            } else {
                throw new InvalidArgumentException(
                    'Invalid stream or file provided for UploadedFile'
                );
            }
        }

        if (!is_int($size)) {
            throw new InvalidArgumentException('Upload file size must be an integer');
        }
        $this->size = $size;

        if (null !== $clientFilename && !is_string($clientFilename)) {
            throw new InvalidArgumentException(
                'Invalid client filename provided for UploadedFile; must be null or a string'
            );
        }
        $this->clientFilename = $clientFilename;

        if (null !== $clientMediaType && !is_string($clientMediaType)) {
            throw new InvalidArgumentException(
                'Invalid client media type provided for UploadedFile; must be null or a string'
            );
        }
        $this->clientMediaType = $clientMediaType;
    }
}
