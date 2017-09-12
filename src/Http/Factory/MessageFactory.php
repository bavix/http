<?php

namespace Bavix\Http\Factory;

use Interop\Http\Factory\RequestFactoryInterface;
use Interop\Http\Factory\ResponseFactoryInterface;
use Bavix\Http\Request;
use Bavix\Http\Response;

class MessageFactory implements \Http\Message\MessageFactory, RequestFactoryInterface, ResponseFactoryInterface
{
    public function createRequest(
        $method,
        $uri,
        array $headers = [],
        $body = null,
        $protocolVersion = '1.1'
    )
    {
        return new Request($method, $uri, $headers, $body, $protocolVersion);
    }

    public function createResponse(
        $statusCode = 200,
        $reasonPhrase = null,
        array $headers = [],
        $body = null,
        $protocolVersion = '1.1'
    )
    {
        return new Response((int)$statusCode, $headers, $body, $protocolVersion, $reasonPhrase);
    }
}
