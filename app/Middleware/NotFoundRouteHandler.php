<?php namespace App\Middleware;


use App\Http\Request;
use App\Http\Response;

class NotFoundRouteHandler extends AbstractApiHandler
{
    public function handle(Request $request) {
        return Response::getInstance([
            'result' => 'error',
            'message' => 'not found'
        ])->addHtml('404')->addHeader('404 Not Found','', 404);
    }

}