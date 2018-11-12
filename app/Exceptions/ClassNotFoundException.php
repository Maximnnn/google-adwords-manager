<?php namespace App\Exceptions;


class ClassNotFoundException extends BawpRequestException
{
    public function resolve()
    {
        return parent::resolve()->addHtml('wrong api route')->addHeader('404 Not Found','', 404);
    }
}