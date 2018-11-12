<?php namespace App\Middleware;

use App\Exceptions\ApiNotLoggedException;
use App\Http\Request;

class ApiLoginHandler  extends AbstractApiHandler
{
    public function handle(Request $request)
    {
        $pass = false;

        $auth = getSetting('auth');

        $username= $auth['user'];
        $password = $auth['psw'];

        if (
            $request->server('PHP_AUTH_USER') === $username &&
            $request->server('PHP_AUTH_PW') === $password
        ) $pass = true;


        if (!$pass)
            throw new ApiNotLoggedException('', $request);
        return parent::handle($request);
    }
}