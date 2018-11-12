<?php namespace App\Helpers;

use App\Exceptions\BawpRequestException;
use App\Exceptions\SimpleRequestException;
use App\Helpers\System;
use App\Exceptions\UploadFileException;

/**
 *
 * File object
 *
 * @param $filename string Name that file will be saved or is saved.
 * @param $filepath string Path that file will be saved or is saved.
 * @param $query    array  Query That will be inserted into database, if needed. Null will do nothing.
 * @param $type     string File type.
 * @param $tmp_name string File tmp name from upload.
 * @param $error    int    File upload error.
 * @param $primarychange bool varaible for reseting primary files in database. Works only if $query['source'] isset and primarychange is true.
 * @param $extension string Files extension.
 *
 * */
class File {
    private $filename = null,

            $filepath = null,

            $query = null,

            $type       = null,

            $tmp_name   = null,

            $error      = 0,

            $size       = null,

            $primarychange = null,

            $extension  = null;

    public function __construct($file = null){
        if( !is_array($file) ) {

            $file_info = pathinfo($file);

            $this->filepath  = $file;
            $this->filename  = $file_info['basename'];
            $this->extension = $file_info['extension'];

        } else {
            if( isset($file['name']) )
                $this->filename = $file['name'];

            if( isset($file['type']) )
                $this->type = $file['type'];

            if( isset($file['tmp_name']) )
                $this->tmp_name = $file['tmp_name'];

            if( isset($file['error']) )
                $this->error = $file['error'];

            if( isset($file['size']) )
                $this->size = $file['size'];
        }
    }

    public function move($from=null, $to=null){}

    public function moveUpload(){

        return move_uploaded_file($this->tmp_name, $this->filepath.'/'.$this->filename);

    }

    public function save(){

        if($this->error == UPLOAD_ERR_OK)
        {

            if (!file_exists($this->filepath))
            {
                $output['mkdir'] = mkdir ($this->filepath,0777);
            }

            if (!file_exists($this->filepath.'/'.$this->filename))
            {

                $this->unsetPrimaryFileBySource();

                $output["move"] =   $this->moveUpload();

                if( !empty($this->query) ) {
                    $output["insert"] = insert_from_array("t_files", $this->query);
                    updateProjectLifecycle($this->query["project_id"]);
                } else
                    $output["insert"] = 'Query empty. No insert.';

            }else{
                throw new SimpleRequestException('File already exists!');
            }

        } else {

            throw new UploadFileException($this->error);

        }

        //$this->delete();

        return $output;
    }

    public function render(){
        readfile($this->filepath);
    }

    public function delete(){

        if( !empty($this->filepath) && !empty($this->filename) ){

            $fileFullPath = $this->filepath.'/'.$this->filename;

            delete_from_array('t_files', "WHERE path = '$fileFullPath'");

            if(file_exists($fileFullPath))
                if( !unlink($fileFullPath) ) //deleteing file
                    throw new BawpRequestException('Can\'t delete file.');

        }


    }

    public function unsetPrimaryFileBySource(){
        if($this->primarychange AND isset($this->query["source"]))
        {
            update_from_array('t_files', 'WHERE source="'.$this->query["source"].'" AND step="invoice manufacturer"',array("primary_file" => NULL));
        }
    }

    public function getSize(){

        $this->size = filesize($this->filepath);

        return $this->size;

    }

    public function getType() {
        $this->setType();

        return $this->type;
    }

    public function getFilePath(){
        return $this->filepath;
    }

    /**
     * @param null $primarychange
     */
    public function setPrimarychange($primarychange)
    {
        $this->primarychange = $primarychange;
    }

    /**
     * @param null $filepath
     */
    public function setFilepath($filepath)
    {
        $this->filepath = SITE_ROOT.'/'.$filepath;
    }

    /**
     * @return mixed|null
     */
    public function getFilename()
    {
        return $this->filename;
    }

    /**
     * @param mixed|null $type
     */
    public function setType($type = null)
    {
        if( empty($type) ) {
            if(empty($this->type) && !empty($this->tmp_name))
                $type = mime_content_type($this->tmp_name);

            if(empty($this->type) && !empty($this->filepath))
                $type = mime_content_type($this->filepath);
        }

        $this->type = $type;
    }

    /**
     * @return null
     */
    public function getQuery($item)
    {
        if( isset($item) )
            return $this->query[$item];


        return $this->query;
    }

    /**
     * @param null $query
     */
    public function setQuery($query)
    {
        $this->query = $query;
    }

    public function setQueryItem($key, $param)
    {
        $this->query[$key] = $param;
    }
}