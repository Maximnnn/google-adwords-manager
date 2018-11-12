<?php

require_once 'vendor/autoload.php';
require_once 'config.php';
require_once 'functions.php';

use App\Http\Request;

/**@var $response \App\Http\Response*/
$response = \App\Kernel::runInnerApi(Request::fromGlobals());

try {
    \App\Http\ResponseResolver::getInstance()->resolve($response);
} catch (\App\Exceptions\BawpResponseException $e){
    $response = $e->resolve();
    \App\Http\ResponseResolver::getInstance()->resolve($response);
} catch (\Exception $e){
    dj(array('result' => 'error', 'message' => $e->getMessage()));
}

