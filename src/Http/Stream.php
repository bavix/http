<?php

namespace Bavix\Http;

use Psr\Http\Message\StreamInterface;

class Stream implements StreamInterface
{

    /**
     * @var resource
     */
    protected $stream;

    /**
     * @var int|mixed|null
     */
    protected $size;

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
     * @var string
     */
    protected $uri;

    /**
     * @var array
     */
    protected $meta;

    /**
     * Stream constructor.
     *
     * @param resource $stream
     * @param array    $options
     */
    public function __construct($stream, array $options = [])
    {
        if (!is_resource($stream))
        {
            throw new \InvalidArgumentException('Stream must be a resource');
        }

        $this->stream   = $stream;
        $this->meta     = stream_get_meta_data($stream);
        $this->seekable = $this->meta['seekable'];
        $this->uri      = $this->meta['uri'];
        $this->readable = is_readable($this->uri);
        $this->writable = is_writable($this->uri);
        $this->size     = $options['size'] ?? $this->getSize();
    }

    /**
     * @return void
     */
    public function __destruct()
    {
        $this->close();
    }

    /**
     * @inheritdoc
     *
     * @return string
     */
    public function __toString()
    {
        try
        {
            return (string)$this
                ->rewind()
                ->getContents();
        }
        catch (\Exception $exception)
        {
            return '';
        }
    }

    /**
     * @inheritdoc
     *
     * @return bool|string
     */
    public function getContents()
    {
        $contents = stream_get_contents($this->stream);

        if ($contents === false)
        {
            throw new \RuntimeException('Unable to read stream contents');
        }

        return $contents;
    }

    /**
     * @inheritdoc
     *
     * @return void
     */
    public function close()
    {
        if (is_resource($this->stream))
        {
            fclose($this->stream);
        }

        $this->detach();
    }

    /**
     * @inheritdoc
     *
     * @return null|resource
     */
    public function detach()
    {
        if (!$this->stream)
        {
            return null;
        }

        $result         = $this->stream;
        $this->stream   = null;
        $this->size     = null;
        $this->uri      = null;
        $this->readable = false;
        $this->writable = false;
        $this->seekable = false;

        return $result;
    }

    /**
     * @inheritdoc
     *
     * @return int|null
     */
    public function getSize()
    {
        if ($this->size !== null)
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

        $stats      = fstat($this->stream);
        $this->size = $stats['size'] ?? null;

        return $this->size;
    }

    /**
     * @inheritdoc
     *
     * @return bool
     */
    public function isReadable()
    {
        return $this->readable;
    }

    /**
     * @inheritdoc
     *
     * @return bool
     */
    public function isWritable()
    {
        return $this->writable;
    }

    /**
     * @inheritdoc
     *
     * @return mixed
     */
    public function isSeekable()
    {
        return $this->seekable;
    }

    /**
     * @inheritdoc
     *
     * @return bool
     */
    public function eof()
    {
        return !$this->stream || feof($this->stream);
    }

    /**
     * @inheritdoc
     *
     * @return bool|int
     */
    public function tell()
    {
        $result = ftell($this->stream);

        if ($result === false)
        {
            throw new \RuntimeException('Unable to determine stream position');
        }

        return $result;
    }

    /**
     * @inheritdoc
     *
     * @return $this
     */
    public function rewind()
    {
        $this->seek(0);

        return $this;
    }

    /**
     * @inheritdoc
     *
     * @param int $offset
     * @param int $whence
     *
     * @return $this
     */
    public function seek($offset, $whence = SEEK_SET)
    {
        if (!$this->seekable)
        {
            throw new \RuntimeException('Stream is not seekable');
        }

        if (fseek($this->stream, $offset, $whence) === -1)
        {
            throw new \RuntimeException('Unable to seek to stream position '
                . $offset . ' with whence ' . var_export($whence, true));
        }

        return $this;
    }

    /**
     * @inheritdoc
     *
     * @param int $length
     *
     * @return bool|string
     */
    public function read($length)
    {
        if (!$this->readable || !$length)
        {
            throw new \RuntimeException('Cannot read from non-readable stream');
        }

        if ($length < 0)
        {
            throw new \RuntimeException('Length parameter cannot be negative');
        }

        $string = fread($this->stream, $length);

        if (false === $string)
        {
            throw new \RuntimeException('Unable to read from stream');
        }

        return $string;
    }

    /**
     * @inheritdoc
     *
     * @param string $string
     *
     * @return bool|int
     */
    public function write($string)
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

    /**
     * @inheritdoc
     *
     * @param null $key
     *
     * @return array|mixed|null
     */
    public function getMetadata($key = null)
    {
        if (!$this->stream)
        {
            return $key ? null : [];
        }

        if (isset($this->meta[$key]))
        {
            return $this->meta[$key];
        }

        $meta = stream_get_meta_data($this->stream);

        return $meta[$key] ?? null;
    }

}
