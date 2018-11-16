<?php
namespace Controllers;


use App\Http\BaseController;
use App\Http\Request;
use App\Http\Response;
use Google\AdsApi\AdWords\AdWordsServices;
use Google\AdsApi\AdWords\AdWordsSession;

class Log extends BaseController
{
    public function runExample(AdWordsServices $adWordsServices, AdWordsSession $session, Request $request){
        return ['log' => file_get_contents(SITEROOT . '/log.txt')];
    }

}