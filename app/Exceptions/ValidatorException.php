<?php namespace App\Exceptions;

use App\Http\Response;

class ValidatorException extends BawpRequestException
{
    public function resolve()
    {
        return Response::getInstance(array(
            'result' => 'error',
            'message' => $this->getMessage()
        ))
            ->type(Response::TYPE_JSON)
            ->addHeader('', '', 406);
    }

}