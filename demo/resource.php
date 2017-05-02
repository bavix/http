<?php

include_once dirname(__DIR__) . '/vendor/autoload.php';

$resource = fopen(__FILE__, 'rb');

$stream = new \Bavix\Http\Stream($resource);

var_dump($stream);
