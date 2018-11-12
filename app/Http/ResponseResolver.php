<?php namespace App\Http;

use App\Exceptions\BawpResponseException;
use App\Exceptions\ResponseAlreadySentException;
use App\Exceptions\ResponseTypeNotFoundException;
use App\Helpers\Csv;

class ResponseResolver{

    public static function getInstance(){
        return new ResponseResolver();
    }

    public function resolve(Response $response){
        $this->checkSent($response)
            ->sendHeaders($response)
            ->sendCookies($response)
            ->emitBody($response);
    }

    protected function checkSent(Response $response){
        if (headers_sent() || (ob_get_level() > 0 && ob_get_length() > 0)) {
            throw new ResponseAlreadySentException('Unable to emit response; headers or body already sent', $response);
        }
        return $this;
    }

    protected function sendHeaders(Response $response){
        foreach ($response->getHeaders() as $header){
            header(ucfirst($header['header']) . ':' . $header['value'], true, $header['code']);
        }

        switch ($response->type()){
            case Response::TYPE_JSON:
                header('Content-Type: application/json');
                break;
            case Response::TYPE_HTML:
                header('Content-Type: text/html; charset=utf-8');
                break;
            case Response::TYPE_EXCEL:
                $data = $response->getData();
                $filename = (isset($data['excel_filename'])) ? $data['excel_filename'] : 'file.csv';
                header('Content-type: application/csv');
                header('Content-Disposition: attachment; filename=' . $filename);
                break;
            case Response::TYPE_FILE:
                $file = $response->getFile();
                if (!$file) {
                    throw new BawpResponseException('no file', $response);
                }
                header('Content-Type: ' . $file->getType(), 200);
                header('Content-Length: ' . $file->getSize(), 200);
                break;
            default:
                throw new ResponseTypeNotFoundException($response->type(), $response);
                break;
        }
        return $this;
    }

    protected function sendCookies(Response $response){
        foreach ($response->getCookies() as $cookie){
            setcookie($cookie['name'], $cookie['value'], $cookie['expire'], $cookie['path'], $cookie['secure']);
        }
        return $this;
    }

    protected function emitBody(Response $response){
        switch ($response->type()){
            case Response::TYPE_HTML:
                echo $response->getHtml();
                break;
            case Response::TYPE_JSON:
                echo json_encode($response->getData());
                break;
            case Response::TYPE_EXCEL:
                Csv::createAndReturnCsv($response->getData());
                break;
            case Response::TYPE_FILE:
                $response->getFile()->render();
                break;
            default:
                throw new ResponseTypeNotFoundException($response->type(), $response);
                break;
        }
        return $this;
    }
}