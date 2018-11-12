<?php namespace App\Middleware;

use App\Exceptions\ClassNotFoundException;
use App\Exceptions\MethodNotFoundException;
use App\Http\BaseController;
use App\Http\Request;
use App\Http\Response;

class RouterHandler extends AbstractApiHandler
{
    public function handle(Request $request)
    {
        $route_components = $this->resolveRoute($request);
        $className = $route_components['className'];
        $method = $route_components['method'];
        $id = $route_components['id'];

        // if found -> run and return result
        // else throw NotFound...Exception
        if (class_exists($className)) {
            $reflection = new \ReflectionClass($className);
            if ($reflection->isAbstract() || $reflection->isInterface() /*|| $reflection->isTrait()*/)
                throw new ClassNotFoundException('', $request);

            if ($reflection->getParentClass()->name == 'App\\Http\\BaseController') {
                $obj = $className::getInstance($request, $method);
            } else {
                $obj = new $className($request);
            }

            if (method_exists($obj, $method) && in_array($method, get_class_methods($className))){
                $request->setId($id);
                /**@var $response Response*/
                return $obj->$method($request);
            } else if (empty($method) and is_callable($obj)) {
                return $obj($request);
            }
        }

        return   parent::handle($request);
    }

    protected function resolveRoute(Request $request){
        $uri = $request->getUri();

        $uri_components = explode('/', $uri);
        $className = $method = $id = null;

        // find class and method
        foreach ($uri_components as $key => $component){
            if ($component == 'api'){
                $route = $uri_components;
                $route = array_splice($route, $key + 1);
                $className = 'Controllers\\';
                foreach ($route as $k => $route_elem){
                    if ($route_elem == ucfirst($route_elem)) {
                        $className .= $route_elem . '\\';
                    } else {
                        $className = substr($className, 0, -1);
                        break;
                    }
                }
                //d($className);
                //$className = 'Controllers\\' . ucfirst($uri_components[$key + 1]);

                $method = $route[$k];
                $id = $route[$k + 1];
                break;
            }
        }

        return array(
            'className' => $className,
            'method'    => $method,
            'id'        => $id
        );
    }
}