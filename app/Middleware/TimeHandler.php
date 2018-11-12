<?php namespace App\Middleware;

use App\Http\Request;
use App\Http\Response;

class TimeHandler extends AbstractApiHandler {
    public function handle(Request $request)
    {
        $time = microtime(true);

        /**@var $response Response*/
        $response =  parent::handle($request);

        $response = $response->addHeader('script-time', (microtime(true) - $time), null);

        return $response;

    }
}