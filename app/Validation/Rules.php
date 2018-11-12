<?php namespace App\Validation;


class Rules
{
    protected $rules = array();

    const DATE = 'date';
    const DATETIME = 'datetime';
    const REQUIRED = 'required';
    const INT = 'int';
    const STRING = 'string';

    public static function create($rules){
        $rulesObj = new Rules();
        foreach ($rules as $key => $rule){
            $rulesObj->addRule($key, $rule);
        }
        return $rulesObj;
    }

    public function addRule($key, $rule){
        $rule = explode('|', $rule);
        $this->rules[$key] = $rule;
        return $this;
    }

    public function getRule($rule){
        if (isset($this->rules[$rule]))
            return $this->rules[$rule];
        else
            return array();
    }

    public function getRules(){
        return $this->rules;
    }
}