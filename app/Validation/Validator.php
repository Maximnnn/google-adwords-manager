<?php namespace App\Validation;

use App\Http\Request;
use App\Exceptions\ValidatorException;

class Validator
{

    protected $messages = array(
        self::DATE      => ' should be valid date',
        self::DATETIME  => ' should be valid datetime',
        self::TIME      => ' should be valid time',
        self::REQUIRED  => ' required',
        self::INT       => ' should be integer',
        self::STRING    => ' should be string',
        self::EMAIL     => ' should be valid email',
        self::NOT_EMPTY => ' is empty'
    );

    protected $request_valid = false;
    protected $message;

    const DATE = 'date';
    const DATETIME = 'datetime';
    const TIME = 'time';
    const REQUIRED = 'required';
    const INT = 'int';
    const STRING = 'string';
    const EMAIL = 'email';
    const NOT_EMPTY = 'notempty';

    public static function run(Request $request, Rules $rules, $type = 'all', $throw_exception = true){
        $validator = new Validator();

        return $validator->validate($request, $rules, $type, $throw_exception);
    }

    protected function validate(Request $request, Rules $rules, $type = 'all', $throw_exception = true){

        $resolved = $this->resolveValidation($request, $rules , $type);

        $this->message = implode('|', $resolved['message']);

        if ($resolved['valid'] === true){
            $this->request_valid = true;
            return $this;
        } else {
            if ($throw_exception === true) {
                throw new ValidatorException($this->message, $request);
            } else {
                $this->request_valid = false;
                return $this;
            }
        }
    }

    public function passed(){
        return $this->request_valid;
    }
    public function failed(){
        return !$this->request_valid;
    }
    public function getMessage(){
        return $this->message;
    }

    protected function resolveValidation(Request $request, Rules $rules, $type){

        $validate = true;
        $message = array();

        foreach ($rules->getRules() as $key => $rules_arr){

            if (strtolower($type) == 'get') {
                $request_value = $request->get($key, null, null);
            } else if (strtolower($type) == 'post') {
                $request_value = $request->post($key, null, null);
            } else {
                $request_value = $request->requestAll($key, null, null);
            }
            $value_in_array = array();

            foreach ($rules_arr as $rule){

                switch ($rule){
                    case self::REQUIRED:
                        if (is_null($request_value)) {
                            $validate = false;
                            $message[] = $key . $this->messages[self::REQUIRED];
                        }
                        break;
                    case self::DATE:
                        if (!self::valid_date($request_value) && !is_null($request_value)){
                            $validate = false;
                            $message[] = $key . $this->messages[self::DATE];
                        }
                        break;
                    case self::DATETIME:
                        if (!self::valid_datetime($request_value) && !is_null($request_value)){
                            $validate = false;
                            $message[] = $key . $this->messages[self::DATETIME];
                        }
                        break;
                    case self::TIME:
                        if (!self::valid_time($request_value) && !is_null($request_value)){
                            $validate = false;
                            $message[] = $key . $this->messages[self::TIME];
                        }
                        break;
                    case self::INT:
                        if ((string)$request_value !== (string)intval($request_value) && !is_null($request_value)){
                            $validate = false;
                            $message[] = $key . $this->messages[self::INT];
                        }
                        break;
                    case self::STRING:
                        if (!is_string($request_value) & $request_value !== null){
                            $validate = false;
                            $message[] = $key . $this->messages[self::STRING];
                        }
                        break;
                    case self::EMAIL:
                        if (!filter_var($request_value, FILTER_VALIDATE_EMAIL) && !is_null($request_value)){
                            $validate = false;
                            $message[] = $key . $this->messages[self::EMAIL];
                        }
                        break;
                    case self::NOT_EMPTY:
                        if ($request_value == '' && !is_null($request_value)){
                            $validate = false;
                            $message[] = $key . $this->messages[self::NOT_EMPTY];
                        }
                        break;
                    default:
                        $sign = substr($rule, 0,1);
                        $val = substr($rule, 1);
                        switch ($sign) {
                            case '>':
                                if ($request_value <= $val && $request_value != null){
                                    $validate = false;
                                    $message[] = $key . ' should be > ' . $val;
                                }
                                break;
                            case '<':
                                if ($request_value >= $val && $request_value != null){
                                    $validate = false;
                                    $message[] = $key . ' should be < ' . $val;
                                }
                                break;
                            case '!':
                                if ($request_value == $val && $request_value != null){
                                    $validate = false;
                                    $message[] = $key . ' should be <> ' . $val;
                                }
                                break;
                            default:
                                $value_in_array[] = $rule;
                                break;
                        }

                        break;
                }
            }

            if (!empty($value_in_array) && $request_value !== null){
                if (!in_array($request_value, $value_in_array)){
                    $validate = false;
                    $message[] = $key . ' shoud be in ' . implode(',', $value_in_array);
                }
            }

        }

        return array(
            'valid' => $validate,
            'message' => $message
        );
    }

    public static function valid_date($date){
        $format = DATE_FORMAT;
        $d = \DateTime::createFromFormat($format, $date);
        return $d && ($d->format($format) === $date || $d->format('n/j/y') === $date);
    }

    public static function valid_datetime($datetime){
        $format = DATETIME_FORMAT;
        $d = \DateTime::createFromFormat($format, $datetime);
        return $d && ($d->format($format) === $datetime || $d->format('n/j/y h:iA') === $datetime);
    }

    public static function valid_time($time, $format = TIME_FORMAT){
        $d = \DateTime::createFromFormat($format, $time);
        return $d && $d->format($format) === $time;
    }



}