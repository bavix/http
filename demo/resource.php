<?php

include_once dirname(__DIR__) . '/vendor/autoload.php';

$resource = fopen(__FILE__, 'rb');

$stream = \Bavix\Http\Stream::createFromResource($resource);

var_dump($stream);
