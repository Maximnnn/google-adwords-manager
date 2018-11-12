<?php namespace App\Exceptions;

class ResponseAlreadySentException extends BawpResponseException
{
    public function resolve()
    {
        return parent::resolve()->addHtml($this->getMessage());
    }
}