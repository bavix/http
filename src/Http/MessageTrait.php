<?php

namespace Bavix\Http;

use Bavix\Http\Factory\StreamFactory;
use Psr\Http\Message\StreamInterface;

trait MessageTrait
{
    /**
     * @var array Map of all registered headers, as original name => array of values
     */
    protected $headers = [];

    /**
     * @var array Map of lowercase header name => original name at registration
     */
    protected $headerNames = [];

    /**
     * @var string
     */
    protected $protocol = '1.1';

    /**
     * @var StreamInterface
     */
    protected $stream;

    public function getProtocolVersion() : string
    {
        return $this->protocol;
    }

    public function withProtocolVersion($version) : self
    {
        if ($this->protocol === $version)
        {
            return $this;
        }

        $new           = clone $this;
        $new->protocol = $version;

        return $new;
    }

    public function getHeaders(): array
    {
        return $this->headers;
    }

    public function hasHeader($header): bool
    {
        return isset($this->headerNames[strtolower($header)]);
    }

    public function getHeader($header): array
    {
        $header = strtolower($header);

        if (!isset($this->headerNames[$header]))
        {
            return [];
        }

        $header = $this->headerNames[$header];

        return $this->headers[$header];
    }

    public function getHeaderLine($header): string
    {
        return implode(', ', $this->getHeader($header));
    }

    public function withHeader($header, $value): self
    {
        if (!is_array($value))
        {
            $value = [$value];
        }

        $value      = $this->trimHeaderValues($value);
        $normalized = strtolower($header);

        $new = clone $this;

        if (isset($new->headerNames[$normalized]))
        {
            unset($new->headers[$new->headerNames[$normalized]]);
        }

        $new->headerNames[$normalized] = $header;
        $new->headers[$header]         = $value;

        return $new;
    }

    public function withAddedHeader($header, $value): self
    {
        if (!is_array($value))
        {
            $value = [$value];
        }
        else
        {
            $value = array_values($value);
        }

        $value      = $this->trimHeaderValues($value);
        $normalized = strtolower($header);

        $new = clone $this;

        if (isset($new->headerNames[$normalized]))
        {
            $header                = $this->headerNames[$normalized];
            $new->headers[$header] = array_merge($this->headers[$header], $value);
        }
        else
        {
            $new->headerNames[$normalized] = $header;
            $new->headers[$header]         = $value;
        }

        return $new;
    }

    public function withoutHeader($header): self
    {
        $normalized = strtolower($header);

        if (!isset($this->headerNames[$normalized]))
        {
            return $this;
        }

        $header = $this->headerNames[$normalized];

        $new = clone $this;
        unset($new->headers[$header], $new->headerNames[$normalized]);

        return $new;
    }

    public function getBody(): StreamInterface
    {
        if (!$this->stream)
        {
            $this->stream = (new StreamFactory())->createStream('');
        }

        return $this->stream;
    }

    public function withBody(StreamInterface $body): self
    {
        if ($body === $this->stream)
        {
            return $this;
        }

        $new         = clone $this;
        $new->stream = $body;

        return $new;
    }

    protected function setHeaders(array $headers) : void
    {
        $this->headerNames = $this->headers = [];

        foreach ($headers as $header => $value)
        {
            $value = (array)$value;

            $value      = $this->trimHeaderValues($value);
            $normalized = strtolower($header);

            if (isset($this->headerNames[$normalized]))
            {
                $header                 = $this->headerNames[$normalized];
                $this->headers[$header] = array_merge($this->headers[$header], $value);
            }
            else
            {
                $this->headerNames[$normalized] = $header;
                $this->headers[$header]         = $value;
            }
        }
    }

    protected function trimHeaderValues(array $values): array
    {
        return array_map(function ($value) {

            return trim($value, " \t");

        }, $values);
    }
}
