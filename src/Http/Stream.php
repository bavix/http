<?php

namespace Bavix\Http;

use Psr\Http\Message\StreamInterface;

class Stream implements StreamInterface
{
    /**
     * @var resource A resource reference
     */
    protected $stream;

    /**
     * @var bool
     */
    protected $seekable;

    /**
     * @var bool
     */
    protected $readable;

    /**
     * @var bool
     */
    protected $writable;

    /**
     * @var array|mixed|null|void
     */
    protected $uri;

    /**
     * @var int
     */
    protected $size;

    /**
     * @var array Hash of readable and writable stream types
     */
    protected static $readWriteHash = [
        'read'  => [
            'r'   => true, 'w+' => true, 'r+' => true, 'x+' => true, 'c+' => true,
            'rb'  => true, 'w+b' => true, 'r+b' => true, 'x+b' => true,
            'c+b' => true, 'rt' => true, 'w+t' => true, 'r+t' => true,
            'x+t' => true, 'c+t' => true, 'a+' => true,
        ],
        'write' => [
            'w'   => true, 'w+' => true, 'rw' => true, 'r+' => true, 'x+' => true,
            'c+'  => true, 'wb' => true, 'w+b' => true, 'r+b' => true,
            'x+b' => true, 'c+b' => true, 'w+t' => true, 'r+t' => true,
            'x+t' => true, 'c+t' => true, 'a' => true, 'a+' => true,
        ],
    ];

    protected function __construct()
    {
    }

    /**
     * @param resource $resource
     *
     * @return Stream
     */
    public static function createFromResource($resource): Stream
    {
        if (!is_resource($resource))
        {
            throw new \InvalidArgumentException('Stream must be a resource');
        }

        $obj           = new self();
        $obj->stream   = $resource;
        $meta          = stream_get_meta_data($obj->stream);
        $obj->seekable = $meta['seekable'];
        $obj->readable = isset(self::$readWriteHash['read'][$meta['mode']]);
        $obj->writable = isset(self::$readWriteHash['write'][$meta['mode']]);
        $obj->uri      = $obj->getMetadata('uri');

        return $obj;
    }

    /**
     * @param string $content
     *
     * @return Stream
     */
    public static function create(string $content): Stream
    {
        $resource = fopen('php://temp', 'rwb+');
        $stream   = self::createFromResource($resource);
        $stream->write($content);

        return $stream;
    }

    /**
     * Closes the stream when the destructed.
     */
    public function __destruct()
    {
        $this->close();
    }

    public function __toString(): string
    {
        try
        {
            if ($this->isSeekable())
            {
                $this->seek(0);
            }

            return $this->getContents();
        }
        catch (\Exception $e)
        {
            return '';
        }
    }

    public function close() : void
    {
        if ($this->stream)
        {
            if (is_resource($this->stream))
            {
                fclose($this->stream);
            }

            $this->detach();
        }
    }

    public function detach()
    {
        if (!$this->stream)
        {
            return null;
        }

        $result = $this->stream;
        unset($this->stream);
        $this->size     = $this->uri = null;
        $this->readable = $this->writable = $this->seekable = false;

        return $result;
    }

    public function getSize()
    {
        if ($this->size)
        {
            return $this->size;
        }

        if (!$this->stream)
        {
            return null;
        }

        if ($this->uri)
        {
            clearstatcache(true, $this->uri);
        }

        $stats = fstat($this->stream);

        if (isset($stats['size']))
        {
            $this->size = $stats['size'];

            return $this->size;
        }

        return null;
    }

    public function tell(): int
    {
        $result = ftell($this->stream);

        if ($result === false)
        {
            throw new \RuntimeException('Unable to determine stream position');
        }

        return $result;
    }

    public function eof(): bool
    {
        return !$this->stream || feof($this->stream);
    }

    public function isSeekable(): bool
    {
        return $this->seekable;
    }

    public function seek($offset, $whence = SEEK_SET) : void
    {
        if (!$this->seekable)
        {
            throw new \RuntimeException('Stream is not seekable');
        }

        if (fseek($this->stream, $offset, $whence) === -1)
        {
            throw new \RuntimeException('Unable to seek to stream position ' . $offset . ' with whence ' . var_export($whence, true));
        }
    }

    public function rewind() : void
    {
        $this->seek(0);
    }

    public function isWritable(): bool
    {
        return $this->writable;
    }

    public function write($string): int
    {
        if (!$this->writable)
        {
            throw new \RuntimeException('Cannot write to a non-writable stream');
        }

        $this->size = null;
        $result     = fwrite($this->stream, $string);

        if ($result === false)
        {
            throw new \RuntimeException('Unable to write to stream');
        }

        return $result;
    }

    public function isReadable(): bool
    {
        return $this->readable;
    }

    public function read($length): string
    {
        if (!$this->readable)
        {
            throw new \RuntimeException('Cannot read from non-readable stream');
        }

        return fread($this->stream, $length);
    }

    public function getContents(): string
    {
        if (!$this->stream)
        {
            throw new \RuntimeException('Unable to read stream contents');
        }

        $contents = stream_get_contents($this->stream);

        if ($contents === false)
        {
            throw new \RuntimeException('Unable to read stream contents');
        }

        return $contents;
    }

    public function getMetadata($key = null)
    {
        if (!$this->stream)
        {
            return $key ? null : [];
        }

        if (null === $key)
        {
            return stream_get_meta_data($this->stream);
        }

        $meta = stream_get_meta_data($this->stream);

        return $meta[$key] ?? null;
    }
}
