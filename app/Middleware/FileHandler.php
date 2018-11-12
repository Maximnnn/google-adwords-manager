<?php namespace App\Middleware;

use App\Helpers\File;
use App\Http\Request;
use App\Http\Response;
use App\Helpers\System;

class FileHandler extends AbstractApiHandler {

    public function handle(Request $request) {

        $uri = $request->getUri();

        if (strpos($uri, 'api/files/') !== false) {
            $components = explode('api/files/', $uri);
            $filename   = end($components);

          //  d($filename);

            $system = new System();

            $file = $system->getExistingFileInstance('/files/'.$filename);

          //d('/files/'.$filename);

            return Response::getInstance()->addFile($file)->type(Response::TYPE_FILE);
        }

        return parent::handle($request);
    }
}