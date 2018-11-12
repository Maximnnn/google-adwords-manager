<?php namespace App\Exceptions;

use App\Http\Response;

class BawpResponseException extends \Exception
{
    protected $response;

    public function __construct($message = "", Response $response)
    {
        parent::__construct($message);
        $this->response = $response;
    }

    /**
     * @return Response
     **/
    public function resolve(){
        return Response::getInstance()->type($this->response->type())->addData('result', 'error')->addHtml('error');
    }
}