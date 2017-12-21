<?php

namespace Bavix\Http\Factory;

use Interop\Http\Factory\StreamFactoryInterface;
use Bavix\Http\Stream;
use Psr\Http\Message\StreamInterface;

class StreamFactory implements \Http\Message\StreamFactory, StreamFactoryInterface
{

    /**
     * @param resource|Stream|null $body
     *
     * @return resource|Stream|null
     */
    public function createStream($body = null)
    {
        if ($body instanceof StreamInterface)
        {
            return $body;
        }

        if (\is_resource($body))
        {
            return Stream::createFromResource($body);
        }

        return Stream::create($body ?? '');
    }

    public function createStreamFromFile($file, $mode = 'rb')
    {
        $resource = \fopen($file, $mode);

        return Stream::createFromResource($resource);
    }

    public function createStreamFromResource($resource)
    {
        return Stream::createFromResource($resource);
    }

    /**
     * Copy the contents of a stream into another stream until the given number
     * of bytes have been read.
     *
     * @author Michael Dowling and contributors to guzzlehttp/psr7
     *
     * @param StreamInterface $source Stream to read from
     * @param StreamInterface $dist   Stream to write to
     * @param int             $maxLen Maximum number of bytes to read. Pass -1
     *                                to read the entire stream
     *
     * @throws \RuntimeException on error
     */
    public function copyToStream(StreamInterface $source, StreamInterface $dist, int $maxLen = -1)
    {
        if ($maxLen === -1)
        {
            while (!$source->eof())
            {
                if (!$dist->write($source->read(1048576)))
                {
                    break;
                }
            }

            return;
        }

        $bytes = 0;

        while (!$source->eof())
        {
            $buf = $source->read($maxLen - $bytes);

            if (!($len = \strlen($buf)))
            {
                break;
            }

            $bytes += $len;
            $dist->write($buf);

            if ($bytes === $maxLen)
            {
                break;
            }
        }

    }
}
