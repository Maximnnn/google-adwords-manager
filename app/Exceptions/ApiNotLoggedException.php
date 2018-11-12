<?php
/**
 * Created by PhpStorm.
 * User: musakovs
 * Date: 9/12/18
 * Time: 2:05 PM
 */

namespace App\Exceptions;


use App\Http\Response;

class ApiNotLoggedException extends BawpRequestException
{

    public function resolve()
    {
        return Response::getInstance(array(
            'result'  => 'error',
            'massage' => 'auth'
        ))->type(Response::TYPE_JSON);
    }
}