<?php namespace App\Exceptions;


class UnknownVarType extends BawpRequestException
{
    public function resolve()
    {
        return parent::resolve();
    }
}