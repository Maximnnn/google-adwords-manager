<?php namespace App\Exceptions;

use App\Http\Request;
use App\Http\Response;

class ApiNotExistsException extends BawpRequestException
{
    public function resolve()
    {
        return Response::getInstance(array(
            'result' => 'error',
            'message' => $this->getMessage()
        ))->type(Response::TYPE_JSON);
    }

}