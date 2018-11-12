<?php namespace App\Http;

use App\Helpers\File;

class Response {
    protected $cookies = array();
    protected $headers = array();
    protected $data = array();
    protected $html = '';
    protected $type = null;
    protected $file = null;

    const TYPE_JSON = 'json';
    const TYPE_HTML = 'html';
    const TYPE_EXCEL = 'excel';
    const TYPE_FILE = 'file';

    /**
     * creates Response object
     * @param $data array goes to object $data
     * @return Response
     */
    public static function getInstance($data = null){
        return new Response($data);
    }

    /**
     * @param $result
     * @return Response  if need simple json response {result:true} or {result:false}
     */
    public static function result($result = true){
        return Response::getInstance(array('result' => $result))->type(Response::TYPE_JSON);
    }

    /**
     * @param $message string
     * @param $critical - sends email to developer
     * @return Response with http code 500 (server error)
     */
    public static function error($message = 'error', $critical = false) {
        if ($critical === true) {
            bawp_mail(getsetting('developer_emails'), 'Critical Error!', $message);
        }
        return Response::getInstance()->serverError($message);
    }

    /**
     * @param $message string
     * @return Response with http code 403 (permission denied)
     */
    public static function noPermission($message = 'permission denied') {
        return Response::getInstance()->permissionDenied($message);
    }

    protected function __construct($data = null){
        if ($data) foreach ($data as $key => $value){
            $this->addData($key, $value);
        }
    }

    public function type($type = null){
        if (!empty($type)) {
            $this->type = $type;
            return $this;
        } else
            return $this->type;
    }

    public function addHtml($html){
        $this->html .= $html;
        return $this;
    }

    public function addData($key, $value){
        if (!isset($this->data[$key])) {

            if(is_object($value) and get_class($value) === 'App\Helpers\Collection'){
                $this->data[$key] = $value->toArray();
            } else {
                $this->data[$key] = $value;
            }

        }
        return $this;
    }

    public function addFile(File $file) {
        $this->file = $file;
        return $this;
    }

    /**
     * @return File;
     */
    public function getFile(){
        return $this->file;
    }

    public function getHtml(){
        return $this->html;
    }

    public function getData(){
        return $this->data;
    }

    public function addHeader($header, $value = null, $code = 200){
        $this->headers[] = array(
            'header' => $header,
            'value'  => $value,
            'code'   => $code
        );
        return $this;
    }

    public function getHeaders(){
        return $this->headers;
    }

    public function setCookie($name, $value, $expire = null, $path = '/', $secure = false){
        if ((int)$expire == 0) $expire = strtotime( '+30 days' );
        $this->cookies[] = array(
            'name' => $name,
            'value' => $value,
            'expire' => $expire,
            'path' => $path,
            'secure' => $secure
        );
        return $this;
    }

    public function getCookies(){
        return $this->cookies;
    }

    public function toCsv($excel_fields, $excel_rows, $excel_filename = 'file.csv'){
        $this->data['excel_fields'] = $excel_fields;
        $this->data['excel_rows'] = $excel_rows;
        $this->data['excel_filename'] = $excel_filename;
        $this->type = Response::TYPE_EXCEL;
        return $this;
    }

    public function removeNulls(){
        array_walk_recursive($this->data, function (&$item, $key) {
            $item = is_null($item) ? '' : $item;
        });
        return $this;
    }

    public function addErrorData($message){
        return $this->addData('result', 'error')->addMessage($message);
    }

    public function addMessage($message){
        return $this->addData('message', $message);
    }

    public function serverError($message = 'error') {
        return $this->addHeader('','', 500)->addErrorData($message);
    }

    public function permissionDenied($message) {
        return $this->addHeader('','', 403)->addErrorData($message);
    }
}