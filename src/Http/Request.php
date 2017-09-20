<?php

namespace Bavix\Http;

use InvalidArgumentException;
use Bavix\Http\Factory\StreamFactory;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\StreamInterface;
use Psr\Http\Message\UriInterface;

class Request implements RequestInterface
{
    use MessageTrait;

    /**
     * @var string
     */
    protected $method;

    /**
     * @var null|string
     */
    protected $requestTarget;

    /**
     * @var null|UriInterface
     */
    protected $uri;

    /**
     * @param string                               $method  HTTP method
     * @param string|UriInterface                  $uri     URI
     * @param array                                $headers Request headers
     * @param string|null|resource|StreamInterface $body    Request body
     * @param string                               $version Protocol version
     */
    public function __construct(
        string $method,
        $uri,
        array $headers = [],
        $body = null,
        string $version = '1.1'
    )
    {
        if (!($uri instanceof UriInterface))
        {
            $uri = new Uri($uri);
        }

        $this->method = $method;
        $this->uri    = $uri;
        $this->setHeaders($headers);
        $this->protocol = $version;

        if (!$this->hasHeader('Host'))
        {
            $this->updateHostFromUri();
        }

        if ($body !== '' && $body !== null)
        {
            $this->stream = (new StreamFactory())->createStream($body);
        }
    }

    public function getRequestTarget(): string
    {
        if ($this->requestTarget !== null)
        {
            return $this->requestTarget;
        }

        $target = $this->uri->getPath();

        if ($target === '')
        {
            $target = '/';
        }

        if ($this->uri->getQuery() !== '')
        {
            $target .= '?' . $this->uri->getQuery();
        }

        return $target;
    }

    public function withRequestTarget($requestTarget): self
    {
        if (preg_match('~\s~', $requestTarget))
        {
            throw new InvalidArgumentException('Invalid request target provided; cannot contain whitespace');
        }

        $new                = clone $this;
        $new->requestTarget = $requestTarget;

        return $new;
    }

    public function getMethod(): string
    {
        return $this->method;
    }

    public function withMethod($method): self
    {
        $new         = clone $this;
        $new->method = $method;

        return $new;
    }

    public function getUri(): UriInterface
    {
        return $this->uri;
    }

    public function withUri(UriInterface $uri, $preserveHost = false): self
    {
        if ($uri === $this->uri)
        {
            return $this;
        }

        $new      = clone $this;
        $new->uri = $uri;

        if (!$preserveHost || !$this->hasHeader('Host'))
        {
            $new->updateHostFromUri();
        }

        return $new;
    }

    protected function updateHostFromUri()
    {
        $host = $this->uri->getHost();

        if ($host === '')
        {
            return;
        }

        if (($port = $this->uri->getPort()) !== null)
        {
            $host .= ':' . $port;
        }

        if (isset($this->headerNames['host']))
        {
            $header = $this->headerNames['host'];
        }
        else
        {
            $header                    = 'Host';
            $this->headerNames['host'] = 'Host';
        }
        // Ensure Host is the first header.
        // See: http://tools.ietf.org/html/rfc7230#section-5.4
        $this->headers = [$header => [$host]] + $this->headers;
    }
}
