<?php namespace App\Exceptions;


class MethodNotFoundException extends BawpRequestException
{
    public function resolve()
    {
        return parent::resolve()->addHtml('Method not found <pre>'.$this.'</pre>')->addHeader('404 Not Found','', 404);
    }
}