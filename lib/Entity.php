<?php
abstract class Entity {
       
    public static function constructFromArray(array $data) {
    	return new static($data);
    }

    		    		public function __get($name) {
		if (in_array($name, $this->properties)) {
			return $this->$name;
		} else {
			throw new Exception('Attempt to read non-existant property: ' . __CLASS__ . "::" . $name);
		}
	}

	public function __set($name, $value) {
       if(in_array($name, $this->properties)) {
       	$this->$name = $value;
       } else {
       	throw new Exception("Attempt to set non-existant property: " . __CLASS__ . "::" . $name);
       	
       }
	}

		       	public function __construct(array $cols) {
                   $diff = array_diff(array_keys($cols), $this->properties);
                   if (!empty($diff)) {
                   	throw new Exception("Attempt to initialise non-existant property when creating new instance of " . __CLASS__);
                   } else {
           foreach ($cols as $key => $value) {
	         $this->$key = $value;
           }
	    }
	}

}