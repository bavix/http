<?php

namespace Bavix\Http\Factory;

use InvalidArgumentException;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\UploadedFileInterface;
use Interop\Http\Factory\ServerRequestFactoryInterface;
use Bavix\Http\ServerRequest;
use Bavix\Http\UploadedFile;

class ServerRequestFactory implements ServerRequestFactoryInterface
{

    public function createServerRequest($method, $uri): ServerRequestInterface
    {
        return new ServerRequest($method, $uri);
    }

    public function createServerRequestFromArray(array $server): ServerRequestInterface
    {
        return new ServerRequest(
            static::getMethodFromEnvironment($server),
            static::getUriFromEnvironmentWithHTTP($server),
            [],
            null,
            '1.1',
            $server
        );
    }

    public static function createServerRequestFromArrays(
        array $server,
        array $headers,
        array $cookie,
        array $get,
        array $post,
        array $files
    ): ServerRequestInterface
    {
        $method = static::getMethodFromEnvironment($server);
        $uri    = static::getUriFromEnvironmentWithHTTP($server);

        $protocol = \str_replace('HTTP/', '', $server['SERVER_PROTOCOL'] ?? '1.1');

        $serverRequest = new ServerRequest($method, $uri, $headers, null, $protocol, $server);

        return $serverRequest
            ->withQueryParams($get)
            ->withParsedBody($post)
            ->withUploadedFiles(self::normalizeFiles($files));
    }

    public static function createServerRequestFromGlobals(): ServerRequestInterface
    {
        $server = \filter_input_array(INPUT_SERVER, FILTER_UNSAFE_RAW) ?: [];

        if (false === isset($server['REQUEST_METHOD']))
        {
            $server['REQUEST_METHOD'] = 'GET';
        }

        $headers = [];

        if (\function_exists('getallheaders'))
        {
            $headers = \getallheaders();
        }

        $cookie = \filter_input_array(INPUT_COOKIE, FILTER_UNSAFE_RAW) ?: [];
        $query = \filter_input_array(INPUT_GET, FILTER_UNSAFE_RAW) ?: [];
        $data = \filter_input_array(INPUT_POST, FILTER_UNSAFE_RAW) ?: [];

        return static::createServerRequestFromArrays($server, $headers, $cookie, $query, $data, $_FILES);
    }

    private static function getMethodFromEnvironment(array $environment): string
    {
        if (false === isset($environment['REQUEST_METHOD']))
        {
            throw new InvalidArgumentException('Cannot determine HTTP method');
        }

        return $environment['REQUEST_METHOD'];
    }

    private static function getUriFromEnvironmentWithHTTP(array $environment): \Psr\Http\Message\UriInterface
    {
        $uri = (new UriFactory())
            ->createUriFromArray($environment);

        if ($uri->getScheme() === '')
        {
            $uri = $uri->withScheme('http');
        }

        return $uri;
    }

    private static function normalizeFiles(array $files): array
    {
        $normalized = [];

        foreach ($files as $key => $value)
        {
            if ($value instanceof UploadedFileInterface)
            {
                $normalized[$key] = $value;
            }
            elseif (\is_array($value))
            {
                if (isset($value['tmp_name']))
                {
                    $normalized[$key] = self::createUploadedFileFromSpec($value);
                    continue;
                }

                $normalized[$key] = self::normalizeFiles($value);
            }
            else
            {
                throw new InvalidArgumentException('Invalid value in files specification');
            }
        }

        return $normalized;
    }

    private static function createUploadedFileFromSpec(array $value)
    {
        if (\is_array($value['tmp_name']))
        {
            return self::normalizeNestedFileSpec($value);
        }

        return new UploadedFile(
            $value['tmp_name'],
            (int)$value['size'],
            (int)$value['error'],
            $value['name'],
            $value['type']
        );
    }

    private static function normalizeNestedFileSpec(array $files = []): array
    {
        $normalizedFiles = [];

        foreach (\array_keys($files['tmp_name']) as $key)
        {
            $spec                  = [
                'tmp_name' => $files['tmp_name'][$key],
                'size'     => $files['size'][$key],
                'error'    => $files['error'][$key],
                'name'     => $files['name'][$key],
                'type'     => $files['type'][$key],
            ];
            $normalizedFiles[$key] = self::createUploadedFileFromSpec($spec);
        }

        return $normalizedFiles;
    }
}
