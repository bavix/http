<?php

namespace Bavix\Http;

use Psr\Http\Message\UriInterface;

class Uri implements UriInterface
{
    protected static $schemes = [
        'http'  => 80,
        'https' => 443,
    ];

    protected static $charUnreserved = 'a-zA-Z0-9_\-\.~';
    protected static $charSubDelims  = '!\$&\'\(\)\*\+,;=';

    protected $scheme   = '';
    protected $userInfo = '';
    protected $host     = '';
    protected $port;
    protected $path     = '';
    protected $query    = '';
    protected $fragment = '';

    public function __construct(string $uri = '')
    {
        if ($uri !== '')
        {
            $parts = \parse_url($uri);

            if ($parts === false)
            {
                throw new \InvalidArgumentException('Unable to parse URI: ' . $uri);
            }

            $this->applyParts($parts);
        }
    }

    public function __toString(): string
    {
        return $this->createUriString();
    }

    public function getScheme(): string
    {
        return $this->scheme;
    }

    public function getAuthority(): string
    {
        if ($this->host === '')
        {
            return '';
        }

        $authority = $this->host;

        if ($this->userInfo !== '')
        {
            $authority = $this->userInfo . '@' . $authority;
        }

        if ($this->port !== null)
        {
            $authority .= ':' . $this->port;
        }

        return $authority;
    }

    public function getUserInfo(): string
    {
        return $this->userInfo;
    }

    public function getHost(): string
    {
        return $this->host;
    }

    public function getPort()
    {
        return $this->port;
    }

    public function getPath(): string
    {
        return $this->path;
    }

    public function getQuery(): string
    {
        return $this->query;
    }

    public function getFragment(): string
    {
        return $this->fragment;
    }

    public function withScheme($scheme): self
    {
        $scheme = $this->filterScheme($scheme);

        if ($this->scheme === $scheme)
        {
            return $this;
        }

        $new         = clone $this;
        $new->scheme = $scheme;
        $new->port   = $new->filterPort($new->port);

        return $new;
    }

    public function withUserInfo($user, $password = null): self
    {
        $info = $user;

        if ($password !== '')
        {
            $info .= ':' . $password;
        }

        if ($this->userInfo === $info)
        {
            return $this;
        }

        $new           = clone $this;
        $new->userInfo = $info;

        return $new;
    }

    public function withHost($host): self
    {
        $host = $this->filterHost($host);

        if ($this->host === $host)
        {
            return $this;
        }

        $new       = clone $this;
        $new->host = $host;

        return $new;
    }

    public function withPort($port): self
    {
        $port = $this->filterPort($port);

        if ($this->port === $port)
        {
            return $this;
        }

        $new       = clone $this;
        $new->port = $port;

        return $new;
    }

    public function withPath($path): self
    {
        $path = $this->filterPath($path);

        if ($this->path === $path)
        {
            return $this;
        }

        $new       = clone $this;
        $new->path = $path;

        return $new;
    }

    public function withQuery($query): self
    {
        $query = $this->filterQueryAndFragment($query);

        if ($this->query === $query)
        {
            return $this;
        }

        $new        = clone $this;
        $new->query = $query;

        return $new;
    }

    public function withFragment($fragment): self
    {
        $fragment = $this->filterQueryAndFragment($fragment);

        if ($this->fragment === $fragment)
        {
            return $this;
        }

        $new           = clone $this;
        $new->fragment = $fragment;

        return $new;
    }

    protected function applyParts(array $parts)
    {
        $this->scheme   = $this->filterScheme($parts['scheme'] ?? '');
        $this->userInfo = $parts['user'] ?? '';
        $this->host     = $this->filterHost($parts['host'] ?? '');
        $this->port     = $this->filterPort($parts['port'] ?? null);
        $this->path     = $this->filterPath($parts['path'] ?? '');
        $this->query    = $this->filterQueryAndFragment($parts['query'] ?? '');
        $this->fragment = $this->filterQueryAndFragment($parts['fragment'] ?? '');

        if (isset($parts['pass']))
        {
            $this->userInfo .= ':' . $parts['pass'];
        }
    }

    protected function createUriString(): string
    {
        $scheme    = $this->getScheme();
        $authority = $this->getAuthority();
        $path      = $this->getPath();
        $query     = $this->getQuery();
        $fragment  = $this->getFragment();

        $uri = '';

        if ($scheme !== '')
        {
            $uri .= $scheme . ':';
        }

        if ($authority !== '')
        {
            $uri .= '//' . $authority;
        }

        if ($path !== '')
        {
            if ($path[0] !== '/')
            {
                if ($authority !== '')
                {
                    $path = '/' . $path;
                }
            }
            elseif (isset($path[1]) && $path[1] === '/')
            {
                if ($authority === '')
                {
                    $path = '/' . \ltrim($path, '/');
                }
            }

            $uri .= $path;
        }

        if ($query !== '')
        {
            $uri .= '?' . $query;
        }

        if ($fragment !== '')
        {
            $uri .= '#' . $fragment;
        }

        return $uri;
    }

    protected static function isNonStandardPort(string $scheme, ?int $port): bool
    {
        return !isset(self::$schemes[$scheme]) || $port !== self::$schemes[$scheme];
    }

    protected function filterScheme(string $scheme): string
    {
        return \strtolower($scheme);
    }

    protected function filterHost(string $host): string
    {
        return \strtolower($host);
    }

    protected function filterPort(?int $port)
    {
        if ($port === null)
        {
            return null;
        }

        if (1 > $port || 0xffff < $port)
        {
            throw new \InvalidArgumentException(sprintf('Invalid port: %d. Must be between 1 and 65535', $port));
        }

        return self::isNonStandardPort($this->scheme, $port) ? $port : null;
    }

    protected function filterPath(string $path): string
    {
        return \preg_replace_callback(
            '/(?:[^' . self::$charUnreserved . self::$charSubDelims . '%:@\/]++|%(?![A-Fa-f0-9]{2}))/',
            [$this, 'rawUrlEncode'],
            $path
        );
    }

    protected function filterQueryAndFragment(string $str): string
    {
        return \preg_replace_callback(
            '/(?:[^' . self::$charUnreserved . self::$charSubDelims . '%:@\/\?]++|%(?![A-Fa-f0-9]{2}))/',
            [$this, 'rawUrlEncode'],
            $str
        );
    }

    protected function rawUrlEncode(array $match): string
    {
        return \rawurlencode($match[0]);
    }

}
