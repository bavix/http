<?php

namespace Bavix\Http;

use Bavix\Context\Cookies;
use Bavix\Context\Session;
use Bavix\Router\Router;
use Bavix\Slice\Slice;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamInterface;
use Psr\Http\Message\UploadedFileInterface;
use Psr\Http\Message\UriInterface;

class ServerRequest extends Request implements ServerRequestInterface
{
    /**
     * @var Session
     */
    protected $session;

    /**
     * @var Cookies
     */
    protected $cookies;

    /**
     * @var Slice
     */
    protected $attributes;

    /**
     * @var Slice
     */
    protected $cookieParams = [];

    /**
     * @var Slice
     */
    protected $parsedBody;

    /**
     * @var Slice
     */
    protected $queryParams = [];

    /**
     * @var Slice
     */
    protected $serverParams;

    /**
     * @var Slice|UploadedFileInterface[]
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
        $this->attributes   = new Slice([]);
        $this->serverParams = new Slice($serverParams);

        parent::__construct($method, $uri, $headers, $body, $version);
    }

    public function withRouter(Router $router)
    {
        $new         = clone $this;
        $new->router = $router;

        return $new;
    }

    /**
     * @return Slice
     */
    public function getServerParams()
    {
        return $this->serverParams;
    }

    /**
     * @return Slice|UploadedFileInterface[]
     */
    public function getUploadedFiles()
    {
        return $this->uploadedFiles;
    }

    public function withUploadedFiles(array $uploadedFiles)
    {
        $new                = clone $this;
        $new->uploadedFiles = new Slice($uploadedFiles);

        return $new;
    }

    /**
     * @return Slice
     */
    public function getCookieParams()
    {
        return $this->cookieParams;
    }

    /**
     * @return Cookies
     */
    public function cookies()
    {
        return $this->cookies;
    }

    public function withCookiesContent(Cookies $cookies)
    {
        $new          = clone $this;
        $new->cookies = $cookies;

        return $new;
    }

    /**
     * @return Session
     */
    public function session()
    {
        return $this->session;
    }

    public function withSessionContent(Session $session)
    {
        $new          = clone $this;
        $new->session = $session;

        return $new;
    }

    public function withCookieParams(array $cookies)
    {
        $new               = clone $this;
        $new->cookieParams = new Slice($cookies);

        return $new;
    }

    /**
     * @return Slice
     */
    public function getQueryParams()
    {
        return $this->queryParams;
    }

    public function withQueryParams(array $query)
    {
        $new              = clone $this;
        $new->queryParams = new Slice($query);

        return $new;
    }

    /**
     * @return Slice
     */
    public function getParsedBody()
    {
        return $this->parsedBody;
    }

    public function withParsedBody($data)
    {
        $new             = clone $this;
        $new->parsedBody = new Slice($data);

        return $new;
    }

    /**
     * @return Slice
     */
    public function getAttributes()
    {
        if ($this->router instanceof Router && !$this->routerLoadAttributes)
        {
            $route = $this->router->getRoute(
                $this->getUri()->getPath(),
                $this->getUri()->getHost(),
                $this->getUri()->getScheme()
            );

            $this->attributes->setData(
                $route->getAttributes()
            );

            $this->routerLoadAttributes = true;
        }

        return $this->attributes;
    }

    public function getAttribute($attribute, $default = null)
    {
        return $this->getAttributes()
            ->getData($attribute, $default);
    }

    public function withAttribute($attribute, $value)
    {
        $new                         = clone $this;
        $new->attributes[$attribute] = $value;

        return $new;
    }

    public function withoutAttribute($attribute)
    {
        if (!$this->getAttributes()->offsetExists($attribute))
        {
            return $this;
        }

        $new = clone $this;
        unset($new->attributes[$attribute]);

        return $new;
    }
}
