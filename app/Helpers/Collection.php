<?php namespace App\Helpers;

use \ArrayObject;

class Collection
{

    /**
     *  Returns current object as array
     *
     *  @return object Returns object as array;
     *
     **/
    public function toArray(){

        return $this->objectToArray($this);

    }

    private function objectToArray($object){

        if( !is_object( $object ) && !is_array( $object ) )
        {
            return $object;
        }
        if( is_object( $object ) )
        {
            $object = (array)( $object );
        }

        return array_map( array($this, 'objectToArray'), $object );
    }

    /**
     *  Converts object to array and returns first element
     *
     *  @return array;
     *
     **/
    public function first(){

        return reset($this->toArray());

    }



    /*public function filter($filter){

        if( is_callable($filter) ){

            $iterator = $this->getIterator();


            foreach( $iterator as  $key => $value ){



                if(!$filter($key, $value)){

                    $this->offsetUnset($key);

                }

                $iterator->next();
            }

        }

        return $this;

    }*/
}