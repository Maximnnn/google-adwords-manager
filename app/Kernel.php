<?php namespace App;

use App\Http\Request;
use App\Http\Response;
use App\Middleware\ApiPipeLine;

class Kernel
{
    public static function runInnerApi(Request $request){
        try {
            $response = ApiPipeLine::getPipeLine()
                ->addMiddleware('App\Middleware\TimeHandler')
                ->addMiddleware('App\Middleware\ApiLoginHandler')
                ->addMiddleware('App\Middleware\FileHandler')
                ->addMiddleware('App\Middleware\ResponseTypeHandler')
                ->addMiddleware('App\Middleware\RouterHandler')
                ->addMiddleware('App\Middleware\NotFoundRouteHandler')
                ->pipe($request);

        } catch (\App\Exceptions\BawpRequestException $e) {
            $response = $e->resolve();
        } catch (\Exception $e) {
            $response = Response::getInstance()->addData('message', $e->getMessage())->type($request->getType());
        }

        return $response;
    }

    public static function runApi(Request $request){
        try {
            $response = ApiPipeLine::getPipeLine()
                ->addMiddleware('App\Middleware\TimeHandler')
                ->addMiddleware('App\Middleware\ApiLoginHandler')
                ->addMiddleware('App\Middleware\ApiRouterHandler')
                ->pipe($request);

        } catch (\App\Exceptions\BawpRequestException $e) {
            $response = $e->resolve();
        } catch (\Exception $e) {
            $response = Response::getInstance()->addData('message', $e->getMessage())->type($request->getType());
        }

        return $response;
    }
}