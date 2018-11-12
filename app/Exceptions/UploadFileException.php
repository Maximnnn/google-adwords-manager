<?php namespace App\Exceptions;

use App\Http\Request;
use App\Http\Response;

class UploadFileException extends BawpRequestException
{
    public function __construct($errorCode)
    {
        parent::__construct();

        $this->setMessage($errorCode);
    }

    public function setMessage($errorCode)
    {

        switch($errorCode){
            case UPLOAD_ERR_INI_SIZE:
                $message = 'Error: File size is too big!.';
                break;
            case UPLOAD_ERR_FORM_SIZE:
                $message = 'Error: File size is too big!';
                break;
            case UPLOAD_ERR_PARTIAL:
                $message = 'Error: Uploading interrupted.';
                break;
            case UPLOAD_ERR_NO_FILE:
                $message = 'Error: No file uploaded.';
                break;
            case UPLOAD_ERR_NO_TMP_DIR:
                $message = 'Error: Server upload error.';
                break;
            case UPLOAD_ERR_CANT_WRITE:
                $message= 'Error: possible file error.';
                break;
            case  UPLOAD_ERR_EXTENSION:
                $message = 'Error: File upload was not completed!';
                break;
            default: $message = 'Error: File upload was not completed. Error unknown';
                break;
        }

        $this->message = $message;
    }

    public function resolve()
    {
        return parent::resolve();
    }
}