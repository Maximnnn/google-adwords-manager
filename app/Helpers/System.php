<?php namespace App\Helpers;


class System
{
    /**
     *  Finds file from project directory;
     *
     *  @param string $filepath File path without root path.
     *  @return object|bool Returns File object or false if no file; //TODO need to change false to 404 not found error.
     **/
    public function getExistingFileInstance($filepath) {

        if (file_exists(ROOTPATH . '/' . $filepath)) {
            $file = new File(ROOTPATH . '/' . $filepath);
        } else {
            $file = false;
        }

        return $file;
    }



}