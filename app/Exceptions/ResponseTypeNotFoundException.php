<?php namespace App\Exceptions;

class ResponseTypeNotFoundException extends BawpResponseException {
    public function resolve()
    {
        return parent::resolve()->addHtml($this->getMessage());
    }
}