<?php

function pn($data){
    echo '<pre>';
    print_r($data);
    echo '</pre>';
}

function d($data) {
    pn($data);
    die;
}

function dj($data) {
    pn(json_encode($data));
    die;
}

function check_url($url) {
    $ch = curl_init($url);

    curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);        // dont check https
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);        // dont check https
    curl_setopt($ch, CURLOPT_HEADER  , true);           // header
    curl_setopt($ch, CURLOPT_NOBODY  , true);           // no body

    $output = (curl_exec($ch));
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($code == 200) {
        return true;
    }

    return false;
}

function getSetting($key) {
    $settings = require_once SITEROOT . '/settings.php';

    if (array_key_exists($key, $settings)) return $settings[$key];

    return null;
}
