<?php namespace App\Middleware;

use App\Http\Request;

interface ApiHandler
{
    public function setNext(ApiHandler $handler);

    public function handle(Request $request);
}
