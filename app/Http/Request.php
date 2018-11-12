<?php namespace App\Http;

use App\Exceptions\UnknownVarType;
use App\Helpers\Collection;
use App\Helpers\File;
use App\Validation\Rules;
use App\Validation\Validator;

class Request {
    protected $cookies = array();
    protected $get = array();
    protected $post = array();
    protected $files = array();
    protected $server = array();
    protected $session = array();
    protected $uri = '';
    protected $user = null;
    protected $id = null;
    protected $start_time = null;

    const TYPE_HTML = 'html';
    const TYPE_JSON = 'json';
    const TYPE_EXCEL = 'excel';
    const TYPE_FILE = 'file';

    protected static $request = null;


    /**
     * @return Request
     */
    public static function getInstance(){
        if (is_null(self::$request)) {
            self::$request = new Request();
        }
        return self::$request;
    }

    protected function __construct(){}

    public static function fromGlobals(){
        $request = self::getInstance();
        $request->start_time = date('Y-m-d H:i:s');
        $request->cookies = $_COOKIE;
        $request->get = $_GET;
        $request->post = $_POST;
        $request->files = $_FILES;
        $request->server = $_SERVER;
        $request->session = $_SESSION;
        $request->user = new User($_SESSION['usr']);

        $uri = $_SERVER['REQUEST_URI'];
        $request->uri = parse_url($uri, PHP_URL_PATH);

        return $request;
    }

    public static function fromFakeRequestData($data, $request_uri = '/api/Tracking/getTrackingVisitors'){  // for testing or...
        $request = self::getInstance();
        if (isset($data['cookie'])) $request->cookies = $data['cookie'];
        if (isset($data['get'])) $request->get = $data['get'];
        if (isset($data['post'])) $request->post = $data['post'];
        if (isset($data['files'])) $request->files = $data['files'];
        if (isset($data['server'])) $request->server = $data['server'];
        if (isset($data['session'])) $request->session = $data['session'];
        if (isset($data['session'])) new User($request->user = $data['session']['usr']);

        $request->uri = $request_uri;
        return $request;
    }

    public function getUri(){
        return $this->uri;
    }

    /**
     * @return string - request expected type (html|json)
     */
    public function getType(){
        if (preg_match('/html/', $this->server['HTTP_ACCEPT']))
            return self::TYPE_HTML;
        if (preg_match('/json/', $this->server['HTTP_ACCEPT']))
            return self::TYPE_JSON;

        return self::TYPE_JSON;
    }

    /**
     *  Gets one specific variable or all $_GET variables from Request and escapes them.
     *
     *  @param string $parameter Which param to get from object;
     *  @param string $type Parameter type to return;
     *  @param string $default Default parameter to return if empty;
     *  @return mixed Return object or value of specific get object;
     *
     **/
    public function get($parameter = null, $type = null, $default = null){

        return $this->parseGetAndPostWithEscape($this->get, $parameter, $type , $default);

    }

    /**
     *  Gets one specific variable or all $_POST variables from Request and escapes them.
     *
     *  @param string $parameter Which param to get from object;
     *  @param string  $type Parameter type to return;
     *  @param string $default Default parameter to return if empty;
     *  @return mixed Return object or value of specific post object;
     *
     **/
    public function post($parameter = null, $type = null, $default = null){

        return $this->parseGetAndPostWithEscape($this->post, $parameter, $type , $default);

    }

    public function getObj($parameter = null, $type = null, $default = null){
        return $this->arrayToObject($this->parseGetAndPostWithEscape($this->get, $parameter, $type , $default));
    }

    public function postObj($parameter = null, $type = null, $default = null){
        return $this->arrayToObject($this->parseGetAndPostWithEscape($this->post, $parameter, $type , $default));
    }

    public function requestAll($parameter = null, $type = null, $default = null){
        return $this->parseGetAndPostWithEscape(array_merge($this->get, $this->post), $parameter, $type , $default);
    }

    /**
     *  @deprecated have not been updated
     *  Gets one specific variable or all $_POST variables from Request WITHOUT escaping.
     *
     *  @param string $parameter Which param to get from object;
     *  @param string $default Default parameter to return if empty;
     *  @return mixed Return object or value of specific post object;
     *
     **/
    public function getRaw($parameter = null, $default = null){

        $value = $this->getValueFromArray($this->get, $parameter);

        if (is_null($value)) $value = $default;

        return $value;
    }

    /**
     *  @deprecated
     *  Gets one specific variable or all $_POST variables from Request WITHOUT escaping.
     *
     *  @param string $parameter Which param to get from object;
     *  @param string $default Default parameter to return if empty;
     *  @return mixed Return object or value of specific post object;
     *
     **/
    public function postRaw($parameter  = null, $default = null){

        $value = $this->getValueFromArray($this->post, $parameter);

        if (is_null($value)) $value = $default;

        return stripslashes($value);
    }

    public function getId() {
        return mysql_real_escape_string($this->id);
    }

    public function setId($id) {
        if (is_null($this->id))
            $this->id = $id;
        return $this;
    }

    /**
     * @return User
     */
    public function user(){
        return $this->user;
    }

    public function checkPermission($permission){
        return $this->user()->checkPermission($permission);
    }

    /**
     * Validates request, throws ValidatorException if not passed or Validator object if pass
     *
     * @var $rules Rules
     * @var $throw_exception boolean
     * @var $type string all|post|get
     * @return Validator
     */
    public function validate(Rules $rules, $type = 'all' ,$throw_exception = true) {
        return Validator::run($this, $rules, $type, $throw_exception);
    }

    public function server($key = null, $default = null){

        $value = $this->getValueFromArray($this->server, $key);

        if (is_null($value)) $value = $default;

        return $this->escapeDefaultVariable($value);

    }


    /**
     *  Ecursive escapes all arrays
     *
     *  @param array $array Array to escape;
     *  @return object Returns Collection object with recursive escaped variables
     *
     **/
    private function arrayToObject($array){

        $returnObject = new Collection();

        foreach($array as $key => $item){

            $returnObject->{$key} = $item;

        }

        return $returnObject;

    }

    /**
     *  Parses and escapes variable based on type;
     *
     *  @param string $var String to parse and escape;
     *  @param string $type Parse type for variable;
     *  @return string Returns escaped variable string;
     *  @throws UnknownVarType exception
     **/
    private function parseVariable($var, $type) {

        $returnParam = null;

        if( !is_null($type) && gettype($var) !== 'array' && gettype($var) !== 'object' ) {
            if ($type === 'datetime') {
                if (Validator::valid_datetime($var))
                    $returnParam = datetime_format_to_sql($var, DATETIME_FORMAT);
            } else if ($type === 'date') {
                if (Validator::valid_date($var))
                    $returnParam = date_format_to_sql($var, DATE_FORMAT);
            } else if ($type === 'time') {
                if (Validator::valid_time($var))
                    $returnParam = time_format_to_sql($var);
            } else if ($type === 'int') {
                $returnParam = (int)$var;
            } else if ($type === 'string') {
                $returnParam = (string)$var;
            } else if ($type == 'phone'){
                $returnParam = FixPhone($var);
            } else if ($type == 'float'){
                $returnParam = (float)$var;
            } else if ($type === 'money') {
                $returnParam = sanitizeMoney($var);
            } else if ($type === 'text') {
                $returnParam = explode(PHP_EOL, $var);
            } else {
                throw new UnknownVarType('type "' . $type . '" is not valid', Request::getInstance());
            }
        } else {
            $returnParam = $var;
        }
        return $returnParam;
    }


    /**
     *  Gets one specific variable or all $_GET variables from Request and escapes them.
     *
     *  @param string $parameter Which param to get from object;
     *  @param string $type Parameter type to return;
     *  @param mixed $default Default parameter to return if empty;
     *  @return mixed Return object or value of specific get object;
     *
     **/
    private function escapeDefaultVariable($default){
        $returnData = null;
        if( getType($default) !== 'array' ){
            $returnData = $default;
        } else {
            array_walk_recursive(
                $default,
                function (&$value) {
                }
            );

            $returnData = $default;
        }

        return $returnData;
    }

    /**
     *  Gets one specific variable or all $_GET variables from Request and escapes them.
     *
     *  @param array $baseData What is base data from where to get data;
     *  @param string $parameter Which param to get from object;
     *  @param string $type Parameter type to return;
     *  @param string $default Default parameter to return if empty;
     *  @return mixed Return object or value of specific get object;
     *  @throws
     **/
    private function parseGetAndPostWithEscape($baseData, $parameter = null, $type = null, $default = null){

        $value = $this->getValueFromArray($baseData, $parameter);                                                       // get value from $baseData by parameter

        $value = $this->parseVariable($value, $type);

        if (is_null($value)) return $default;

        $value = $this->escapeDefaultVariable($value);                                                                    // escape value

        if ($type == 'text' && is_array($value))
            $value = implode(PHP_EOL, $value);

        return $value;
    }

    protected function getValueFromArray($baseData, $parameter){

        if (is_null($parameter))
            return $baseData;

        $params = explode('.', $parameter);

        foreach ($params as $param){
            if (isset($baseData[$param]) && gettype($baseData) === 'array') {
                $baseData = $baseData[$param];
            } else {
                return null;
            }
        }

        return $baseData;

    }

    public function session($parameter = null, $default = null){

        $value = $this->getValueFromArray($this->session, $parameter);

        if (is_null($value)) $value = $default;

        return $value;

        /*$returnObject = new Collection();

        foreach($this->session as $key => $item){

            $returnObject->{$key} = $item;

        }

        return $returnObject;*/

    }

    public function changeGetValue($value, $new_value){
        $this->get[$value] = $new_value;
        return $this;
    }
    public function changePostValue($value, $new_value){
        $this->post[$value] = $new_value;
        return $this;
    }

    /**
     *  Gets all files associated with Request
     *
     *  @return mixed Return object or value of specific files object;
     *
     **/
    public function files(){
        return $this->files;
    }

    public function filesObj($files){

        if( !isset($files) )
            $files = $this->files;

        $in_order = array();

        foreach($files as $input_field => $file_properties){

            foreach($file_properties as $index => $prop){

                foreach($prop as $key => $data) {

                    $in_order[$input_field][$key][$index] = $data;

                }

            }

        }

        $obj_array = array();

        foreach($in_order as $input_field => $files){

            foreach($files as $file){

                $obj_array[] = new File($file);

            }

        }

        return $obj_array;

    }

    public function datetime() {
        return $this->start_time;
    }
    public function date(){
        return date('Y-m-d', strtotime($this->start_time));
    }
    public function time(){
        return date('H:i:s', strtotime($this->start_time));
    }

}