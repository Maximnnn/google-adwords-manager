<?php namespace App\Middleware;

use App\Exceptions\ApiNotExistsException;
use App\Http\Request;

class ApiRouterHandler extends RouterHandler
{
    protected function resolveRoute(Request $request){
        $allowed_apis = array(
            'Controllers\\Mtool' => array('getMapData', 'getLeadMapData', 'getJobSelect', 'getLeadSelect')
        );

        $uri = $request->getUri();

        $uri_components = explode('/', $uri);
        $className = $method = $id = null;

        // find class and method
        foreach ($uri_components as $key => $component){
            if ($component == 'get.php'){
                $className = 'Controllers\\' . ucfirst($uri_components[$key + 1]);
                $method = $uri_components[$key + 2];
                $id = $uri_components[$key + 3];
                break;
            }
        }

        $route_components = array(
            'className' => $className,
            'method'    => $method,
            'id'        => $id
        );

        if (isset($allowed_apis[$route_components['className']])){
            if (in_array($route_components['method'], $allowed_apis[$route_components['className']]))
                return $route_components;
        }

        throw new ApiNotExistsException('wrong api name', $request);
    }
}

