<?php

declare(strict_types=1);

namespace Bavix\Http\Factory;

use Interop\Http\Factory\UriFactoryInterface;
use Bavix\Http\Uri;
use Psr\Http\Message\UriInterface;

class UriFactory implements \Http\Message\UriFactory, UriFactoryInterface
{
    public function createUri($uri = ''): UriInterface
    {
        if ($uri instanceof UriInterface)
        {
            return $uri;
        }

        return new Uri($uri);
    }

    /**
     * Create a new uri from server variable.
     *
     * @param array $server Typically $_SERVER or similar structure.
     *
     * @return UriInterface
     */
    public function createUriFromArray(array $server): UriInterface
    {
        $uri = new Uri('');

        if (isset($server['REQUEST_SCHEME']))
        {
            $uri = $uri->withScheme($server['REQUEST_SCHEME']);
        }
        elseif (isset($server['HTTPS']))
        {
            $uri = $uri->withScheme($server['HTTPS'] === 'on' ? 'https' : 'http');
        }

        if (isset($server['HTTP_HOST']))
        {
            $uri = $uri->withHost($server['HTTP_HOST']);
        }
        elseif (isset($server['SERVER_NAME']))
        {
            $uri = $uri->withHost($server['SERVER_NAME']);
        }

        if (isset($server['SERVER_PORT']))
        {
            $uri = $uri->withPort($server['SERVER_PORT']);
        }

        if (isset($server['REQUEST_URI']))
        {
            $reqUri  = $server['REQUEST_URI'];
            $data = explode('?', $reqUri);

            $uri  = $uri->withPath(current($data));
        }

        if (isset($server['QUERY_STRING']))
        {
            $uri = $uri->withQuery($server['QUERY_STRING']);
        }

        return $uri;
    }
}
