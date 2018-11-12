<?php namespace App\Middleware;

use App\Http\Request;
use App\Http\Response;

class ResponseTypeHandler extends AbstractApiHandler {
    public function handle(Request $request)
    {
        /**@var $response Response*/
        $response = parent::handle($request);
        $type = $response->type();
        if (empty($type))
            $response = $response->type($request->getType());

        return $response;
    }
}