<?php namespace App\Middleware;

use App\Http\Request;

Class ApiPipeLine {

    protected $pipeLine = array(
        /* 'TimeHandler',
         'RouterHandler'*/
    );

    public static function getPipeLine(){
        return new ApiPipeLine();
    }

    public function __construct(){

    }


    public function addMiddleware($handler){
        $this->pipeLine[] = $handler;
        return $this;
    }

    public function pipe(Request $request){

        $start = new $this->pipeLine[0];

        foreach ($this->pipeLine as $k => $middleware){
            if ($k == 0) continue;
            if (!isset($obj)) {
                $obj = $start;
            }
            $obj = $obj->setNext(new $middleware);
        }

        return $start->handle($request);
    }

}
