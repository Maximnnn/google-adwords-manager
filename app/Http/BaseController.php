<?php namespace App\Http;

use App\Exceptions\PermissionException;
use Google\AdsApi\AdWords\AdWordsServices;
use Google\AdsApi\AdWords\AdWordsSession;
use Google\AdsApi\AdWords\AdWordsSessionBuilder;
use Google\AdsApi\Common\OAuth2TokenBuilder;

abstract class BaseController {

    /**
     * @var $permissions array ('method name' => 'permission')
    */
    protected $permissions = array();

    /**
     * @var $permission string - permission to access to any method in class
     */
    protected $permission = null;

    abstract protected function runExample(AdWordsServices $adWordsServices,AdWordsSession $session, Request $request);

    protected function __construct(Request $request, $method){

        if (is_array($this->permissions) and array_key_exists($method, $this->permissions) and !empty($this->permissions[$method])) {
            if (is_string($this->permissions[$method])) {
                if (!$request->user()->checkPermission($this->permissions[$method])) {
                    throw new PermissionException('access denied to "' . end(explode('\\', get_called_class())) . ' ' . $method . '". You don`t have ' . $this->permissions[$method] . ' permission', $request);
                }
            } else if (is_array($this->permissions[$method])){
                foreach ($this->permissions[$method] as $permission) {
                    if (!$request->user()->checkPermission($permission)) {
                        throw new PermissionException('access denied to "' . end(explode('\\', get_called_class())) . ' ' . $method . '". You don`t have ' . $permission . ' permission', $request);
                    }
                }
            }
        }

        if ($this->permission) {
            if (is_string($this->permission)) {
                if (!$request->user()->checkPermission($this->permission)) {
                    throw new PermissionException('access denied. You don`t have ' . $this->permission . ' permission', $request);
                }
            } else if (is_array($this->permission)) {
                foreach ($this->permissions as $permission) {
                    if (!$request->user()->checkPermission($permission)) {
                        throw new PermissionException('access denied. You don`t have ' . $permission . ' permission', $request);
                    }
                }
            }
        }
    }

    public static function getInstance(Request $request, $method) {
        return new static($request, $method);
    }

    protected function removeNullValues($array) {
        array_walk_recursive($array, function($item, $key) use(&$array) {
            if (is_null($item)) unset($array[$key]);
        });
        return $array;
    }

    public function get(Request $request)
    {
        /*$file = SITEROOT . '/adsapi_php.ini';
        // Generate a refreshable OAuth2 credential for authentication.
        $oAuth2Credential = (new OAuth2TokenBuilder())->fromFile($file)->build();
        // Construct an API session configured from a properties file and the
        // OAuth2 credentials above.
        $session = (new AdWordsSessionBuilder())->fromFile($file)->withOAuth2Credential($oAuth2Credential)->build();*/
        $oAuth2Credential = $this->getOAuth2();
        $session = $this->getSession($oAuth2Credential);
        return Response::getInstance(static::runExample(new AdWordsServices(), $session, $request))->type('json');
    }

    protected function getSession($oAuth2Credential){
        $file = SITEROOT . '/adsapi_php.ini';
        return (new AdWordsSessionBuilder())->fromFile($file)->withOAuth2Credential($oAuth2Credential)->build();
    }

    protected function getOAuth2(){
        $file = SITEROOT . '/adsapi_php.ini';
        return (new OAuth2TokenBuilder())->fromFile($file)->build();
    }
}