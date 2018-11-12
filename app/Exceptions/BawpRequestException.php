<?php namespace App\Exceptions;

use App\Http\Request;
use App\Http\Response;

class BawpRequestException extends \Exception
{

    protected $request;

    public function __construct($message = "", Request $request = null)
    {
        parent::__construct($message);
        if (!$request)
            $this->request = Request::getInstance();
        else
            $this->request = $request;
    }

    public function resolve(){
        return Response::getInstance()
            ->type($this->request->getType())
            ->addData('result', 'error')
            ->addData('message', $this->getMessage())
            ->addHtml('error: ' . $this->getMessage())
            ->addHeader('http 500 Internal Server Error','', 500);
    }

}