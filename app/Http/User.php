<?php namespace App\Http;

class User {
    protected $user = null;
    protected $permissions = array();

    public function __get($name) {
        if (isset($this->user[$name])) {
            return $this->user[$name];
        } else
            return null;
    }

    public function __construct($user) {
        $this->user = $user;
    }

    public function checkPermission($permission) {
        if (empty($this->permissions)) $this->permissions = array_map('strtolower', fetch(bawp_query('select permission from t_permissions where user_id="' . $this->user['id'] . '"'), false, false, true));
        return in_array(strtolower($permission), $this->permissions);
    }
}