<?php namespace App\Middleware;

use App\Http\Request;

abstract class AbstractApiHandler implements ApiHandler
{
    /**
     * @var ApiHandler
     */
    private $nextHandler;

    /**
     * @param ApiHandler $handler
     * @return ApiHandler
     */
    public function setNext(ApiHandler $handler)
    {
        $this->nextHandler = $handler;

        return $handler;
    }

    public function handle(Request $request)
    {
        if ($this->nextHandler) {
            return $this->nextHandler->handle($request);
        } else
            return $request;
    }
}