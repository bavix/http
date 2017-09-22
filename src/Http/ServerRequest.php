<?php

namespace Bavix\Http;

use Bavix\Context\Cookies;
use Bavix\Router\Router;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamInterface;
use Psr\Http\Message\UploadedFileInterface;
use Psr\Http\Message\UriInterface;

class ServerRequest extends Request implements ServerRequestInterface
{
    /**
     * @var array
     */
    protected $attributes = [];

    /**
     * @var array
     */
    protected $cookieParams = [];

    /**
     * @var null|array|object
     */
    protected $parsedBody;

    /**
     * @var array
     */
    protected $queryParams = [];

    /**
     * @var array
     */
    protected $serverParams;

    /**
     * @var UploadedFileInterface[]
     */
    protected $uploadedFiles = [];

    /**
     * @var bool
     */
    protected $routerLoadAttributes;

    /**
     * @var Router
     */
    protected $router;

    /**
     * @var Cookies
     */
    protected $cookies;

    /**
     * @param string                               $method       HTTP method
     * @param string|UriInterface                  $uri          URI
     * @param array                                $headers      Request headers
     * @param string|null|resource|StreamInterface $body         Request body
     * @param string                               $version      Protocol version
     * @param array                                $serverParams Typically the $_SERVER super global
     */
    public function __construct(
        string $method,
        $uri,
        array $headers = [],
        $body = null,
        string $version = '1.1',
        array $serverParams = []
    )
    {
        $this->serverParams = $serverParams;

        parent::__construct($method, $uri, $headers, $body, $version);
    }
    
    public function setCookies(Cookies $cookies)
    {
        $this->cookies = $cookies;

        return $this;
    }

    public function withRouter(Router $router)
    {
        $new         = clone $this;
        $new->router = $router;

        return $new;
    }

    public function getServerParams()
    {
        return $this->serverParams;
    }

    public function getUploadedFiles()
    {
        return $this->uploadedFiles;
    }

    public function withUploadedFiles(array $uploadedFiles)
    {
        $new                = clone $this;
        $new->uploadedFiles = $uploadedFiles;

        return $new;
    }

    public function getCookieParams()
    {
        return $this->cookieParams;
    }

    public function withCookieParams(array $cookies)
    {
        $new               = clone $this;
        $new->cookieParams = $cookies;

        return $new;
    }

    public function getQueryParams()
    {
        return $this->queryParams;
    }

    public function withQueryParams(array $query)
    {
        $new              = clone $this;
        $new->queryParams = $query;

        return $new;
    }

    public function getParsedBody()
    {
        return $this->parsedBody;
    }

    public function withParsedBody($data)
    {
        $new             = clone $this;
        $new->parsedBody = $data;

        return $new;
    }

    public function getAttributes()
    {
        if ($this->router instanceof Router && !$this->routerLoadAttributes)
        {
            $route = $this->router->getRoute(
                $this->getUri()->getPath(),
                $this->getUri()->getHost(),
                $this->getUri()->getScheme()
            );

            $this->attributes = \array_merge(
                $this->attributes,
                $route->getAttributes()
            );

            $this->routerLoadAttributes = true;
        }

        return $this->attributes;
    }

    public function getAttribute($attribute, $default = null)
    {
        if (false === array_key_exists($attribute, $this->getAttributes()))
        {
            return $default;
        }

        return $this->attributes[$attribute];
    }

    public function withAttribute($attribute, $value)
    {
        $new                         = clone $this;
        $new->attributes[$attribute] = $value;

        return $new;
    }

    public function withoutAttribute($attribute)
    {
        if (false === array_key_exists($attribute, $this->getAttributes()))
        {
            return $this;
        }

        $new = clone $this;
        unset($new->attributes[$attribute]);

        return $new;
    }
}
