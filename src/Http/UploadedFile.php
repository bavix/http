<?php

namespace Bavix\Http;

use InvalidArgumentException;
use Bavix\Http\Factory\StreamFactory;
use Psr\Http\Message\StreamInterface;
use Psr\Http\Message\UploadedFileInterface;
use RuntimeException;

class UploadedFile implements UploadedFileInterface
{

    protected static $errors = [
        UPLOAD_ERR_OK,
        UPLOAD_ERR_INI_SIZE,
        UPLOAD_ERR_FORM_SIZE,
        UPLOAD_ERR_PARTIAL,
        UPLOAD_ERR_NO_FILE,
        UPLOAD_ERR_NO_TMP_DIR,
        UPLOAD_ERR_CANT_WRITE,
        UPLOAD_ERR_EXTENSION,
    ];

    protected $clientFilename;

    protected $clientMediaType;

    protected $error;

    protected $file;

    protected $moved = false;
    protected $size;

    /**
     * @var StreamInterface|null
     */
    protected $stream;

    /**
     * @param StreamInterface|string|resource $streamOrFile
     * @param int                             $size
     * @param int                             $errorStatus
     * @param string|null                     $clientFilename
     * @param string|null                     $clientMediaType
     */
    public function __construct(
        $streamOrFile,
        int $size,
        int $errorStatus,
        ?string $clientFilename = null,
        ?string $clientMediaType = null
    )
    {
        $this->setError($errorStatus);
        $this->setSize($size);
        $this->setClientFilename($clientFilename);
        $this->setClientMediaType($clientMediaType);

        if ($this->isOk())
        {
            $this->setStreamOrFile($streamOrFile);
        }
    }

    protected function setStreamOrFile($streamOrFile)
    {
        if (\is_string($streamOrFile))
        {
            $this->file = $streamOrFile;
        }
        elseif (\is_resource($streamOrFile))
        {
            $this->stream = Stream::createFromResource($streamOrFile);
        }
        elseif ($streamOrFile instanceof StreamInterface)
        {
            $this->stream = $streamOrFile;
        }
        else
        {
            throw new InvalidArgumentException('Invalid stream or file provided for UploadedFile');
        }
    }

    /**
     * @param int $error
     *
     * @throws InvalidArgumentException
     */
    protected function setError(int $error)
    {
        if (false === \in_array($error, self::$errors, true))
        {
            throw new InvalidArgumentException('Invalid error status for UploadedFile');
        }

        $this->error = $error;
    }

    /**
     * @param int $size
     *
     * @throws InvalidArgumentException
     */
    protected function setSize(int $size)
    {
        $this->size = $size;
    }

    /**
     * @param string $clientFilename
     *
     * @throws InvalidArgumentException
     */
    protected function setClientFilename(string $clientFilename)
    {
        $this->clientFilename = $clientFilename;
    }

    /**
     * @param string $clientMediaType
     *
     * @throws InvalidArgumentException
     */
    protected function setClientMediaType(string $clientMediaType)
    {
        $this->clientMediaType = $clientMediaType;
    }

    /**
     * @return bool Return true if there is no upload error.
     */
    protected function isOk(): bool
    {
        return $this->error === UPLOAD_ERR_OK;
    }

    /**
     * @throws RuntimeException if is moved or not ok
     */
    protected function validateActive()
    {
        if (false === $this->isOk())
        {
            throw new RuntimeException('Cannot retrieve stream due to upload error');
        }

        if ($this->moved)
        {
            throw new RuntimeException('Cannot retrieve stream after it has already been moved');
        }
    }

    public function getStream()
    {
        $this->validateActive();

        if ($this->stream instanceof StreamInterface)
        {
            return $this->stream;
        }

        $resource = \fopen($this->file, 'rb');

        return Stream::createFromResource($resource);
    }

    public function moveTo($targetPath): void
    {
        $this->validateActive();

        if (empty($targetPath))
        {
            throw new InvalidArgumentException('Invalid path provided for move operation; must be a non-empty string');
        }

        if (null !== $this->file)
        {
            $this->moved = PHP_SAPI === 'cli'
                ? \rename($this->file, $targetPath)
                : \move_uploaded_file($this->file, $targetPath);
        }
        else
        {
            $stream = $this->getStream();

            if ($stream->isSeekable())
            {
                $stream->rewind();
            }

            (new StreamFactory())->copyToStream(
                $stream,
                Stream::createFromResource(
                    \fopen($targetPath, 'wb')
                )
            );

            $this->moved = true;
        }

        if (false === $this->moved)
        {
            throw new RuntimeException(sprintf('Uploaded file could not be moved to %s', $targetPath));
        }
    }

    public function getSize(): int
    {
        return $this->size;
    }

    public function getError(): int
    {
        return $this->error;
    }

    public function getClientFilename(): ?string
    {
        return $this->clientFilename;
    }

    public function getClientMediaType(): ?string
    {
        return $this->clientMediaType;
    }

}
