<?php //-->
/*
 * This file is part of the Eden package.
 * (c) 2009-2011 Christian Blanquera <cblanquera@gmail.com>
 *
 * Copyright and license information can be found at LICENSE.txt
 * distributed with this package.
 */

/**
 * Collection handler complements models for mass updating and handling
 *
 * @package    Eden
 * @category   collection
 * @author     Christian Blanquera <cblanquera@gmail.com>
 * @version    $Id: registry.php 1 2010-01-02 23:06:36Z blanquera $
 */
class Eden_Collection extends Eden_Class implements ArrayAccess, Iterator, Serializable, Countable {
	/* Constants
	-------------------------------*/
	const FIRST = 'first';
	const LAST	= 'last';
	
	/* Public Properties
	-------------------------------*/
	/* Protected Properties
	-------------------------------*/
	protected $_list 	= array();
	protected $_model 	= 'Eden_Model';
	
	/* Private Properties
	-------------------------------*/
	/* Get
	-------------------------------*/
	public static function i(array $data = array()) {
		return self::_getMultiple(__CLASS__,$data);
	}
	
	/* Magic
	-------------------------------*/
	public function __construct(array $data = array()) {
		foreach($data as $row) {
			$this->add($row);
		}
	}
	
	public function __call($name, $args) {
		//if the method starts with get
		if(strpos($name, 'get') === 0) {
			//getUserName('-') - get all rows column values
			$value = isset($args[0]) ? $args[0] : NULL;
			
			//make a new model
			$list = Eden_Model::i();
			//for each row
			foreach($this->_list as $i => $row) {
				//just add the column they want
				//let the model worry about the rest
				$list[] = $row->$name(isset($args[0]) ? $args[0] : NULL);
			}
			
			return $list;
			
		//if the method starts with set
		} else if (strpos($name, 'set') === 0) {
			//setUserName('Chris', '-') - set all user names to Chris
			$value 		= isset($args[0]) ? $args[0] : NULL;
			$separator 	= isset($args[1]) ? $args[1] : NULL;
			
			//for each row
			foreach($this->_list as $i => $row) {
				//just call the method
				//let the model worry about the rest
				$row->$name($value, $separator);
			}
			
			return $this;
		}
		
		//nothing more, just see what the parent has to say
		try {
			return parent::__call($name, $args);
		} catch(Eden_Error $e) {
			Eden_Collection_Error::i($e->getMessage())->trigger();
		}
	}
	
	public function __get($name) {
		//get all rows column values
		$list = Eden_Model::i();
		
		//for each row
		foreach($this->_list as $i => $row) {
			//ad just the name
			$list[] = $row[$name];
		}
		
		return $list;
	}
	
	public function __set($name, $value) {
		//set all rows with this column and value
		foreach($this->_list as $i => $row) {
			$row[$name] = $value;
		}
		
		return $this;
	}
	
	public function __toString() {
		return json_encode($this->get());
	}
	
	/* Public Methods
	-------------------------------*/
	/**
	 * Adds a row to the collection
	 *
	 * @param array|Eden_Model
	 * @return this
	 */
	public function add($row = array()) {
		//Argument 1 must be an array or Eden_Model
		Eden_Collection_Error::i()->argument(1, 'array', $this->_model);
		
		//if it's an array
		if(is_array($row)) {
			//make it a model
			$model = $this->_model;
			$row = $this->$model($row);
		}
		
		//add it now
		$this->_list[] = $row;
		
		return $this;
	}
	
	/**
	 * Adds a row to the collection
	 *
	 * @param string
	 * @param string
	 * @return this
	 */
	public function copy($source, $destination) {
		//Argument Test
		Eden_Collection_Error::i()
			->argument(1, 'string')		//Argument 1 must be a string
			->argument(2, 'string');	//Argument 2 must be a string
		
		//for each row	
		foreach($this->_list as $row) {
			//let the model handle the copying
			$row->copy($source, $destination);
		}
		
		return $this;
	}
	
	public function cut($index = self::LAST) {
		//Argument 1 must be a string or integer
		Eden_Collection_Error::i()->argument(1, 'string', 'int');
		
		//if index is first
		if($index == self::FIRST) {
			//we really mean 0
			$index = 0;
		//if index is last
		} else if($index == self::LAST) {
			//we realy mean the last index number
			$index = count($this->_list) -1;
		}
		
		//if this row is found
		if(isset($this->_list[$index])) {
			//unset it
			unset($this->_list[$index]);
		}
		
		//reindex the list
		$this->_list = array_values($this->_list);
		
		return $this;
	}
	
	/**
	 * Returns the row array
	 *
	 * @param bool
	 * @return array
	 */
	public function get($modified = true) {
		//Argument 1 must be a boolean
		Eden_Collection_Error::i()->argument(1, 'bool');
		
		$array = array();
		//for each row
		foreach($this->_list as $i => $row) {
			//get the array of that (recursive)
			$array[$i] = $row->get($modified);
		}
		
		return $array;
	}
	
	/**
	 * Rewinds the position
	 * For Iterator interface
	 *
	 * @return void
	 */
	public function rewind() {
        reset($this->_list);
    }

	/**
	 * Returns the current item
	 * For Iterator interface
	 *
	 * @return void
	 */
    public function current() {
        return current($this->_list);
    }

	/**
	 * Returns th current position
	 * For Iterator interface
	 *
	 * @return void
	 */
    public function key() {
        return key($this->_list);
    }

	/**
	 * Increases the position
	 * For Iterator interface
	 *
	 * @return void
	 */
    public function next() {
        next($this->_list);
    }

	/**
	 * Validates whether if the index is set
	 * For Iterator interface
	 *
	 * @return void
	 */
    public function valid() {
        return isset($this->_list[key($this->_list)]);
    }
	
	/**
	 * Sets data using the ArrayAccess interface
	 *
	 * @param number
	 * @param mixed
	 * @return void
	 */
	public function offsetSet($offset, $value) {
		//Argument 2 must be an array or Eden_Model
		Eden_Collection_Error::i()->argument(2, 'array', $this->_model);
		
		if(is_array($value)) {
			//make it a model
			$model = $this->_model;
			$value = $this->$model($value);
		}
        
		if (is_null($offset)) {
            $this->_list[] = $value;
        } else {
            $this->_list[$offset] = $value;
        }
    }
	
	/**
	 * isset using the ArrayAccess interface
	 *
	 * @param number
	 * @return bool
	 */
    public function offsetExists($offset) {
        return isset($this->_list[$offset]);
    }
    
	/**
	 * unsets using the ArrayAccess interface
	 *
	 * @param number
	 * @return bool
	 */
	public function offsetUnset($offset) {
		$this->_list = Eden_Model::i($this->_list)
			->cut($offset)
			->get();
    }
    
	/**
	 * returns data using the ArrayAccess interface
	 *
	 * @param number
	 * @return bool
	 */
	public function offsetGet($offset) {
        return isset($this->_list[$offset]) ? $this->_list[$offset] : NULL;
    }
	
	/**
	 * returns serialized data using the Serializable interface
	 *
	 * @return string
	 */
	public function serialize() {
        return $this->__toString();
    }
	
	/**
	 * sets data using the Serializable interface
	 *
	 * @param string
	 * @return void
	 */
    public function unserialize($data) {
        $this->_list = json_decode($data, true);
		return $this;
    }
	
	/**
	 * returns size using the Countable interface
	 *
	 * @return string
	 */
	public function count() {
		return count($this->_list);
	}
	
	/* Protected Methods
	-------------------------------*/
	/* Private Methods
	-------------------------------*/
}

/**
 * Model Errors
 */
class Eden_Collection_Error extends Eden_Error {
	/* Constants
	-------------------------------*/
	const NOT_COLLECTION = 'The data passed into __construct is not a collection.';
	
	/* Public Properties
	-------------------------------*/
	/* Protected Properties
	-------------------------------*/
	/* Private Properties
	-------------------------------*/
	/* Get
	-------------------------------*/
	public static function i($message = NULL, $code = 0) {
		$class = __CLASS__;
		return new $class($message, $code);
	}
	
	/* Magic
	-------------------------------*/
    /* Public Methods
	-------------------------------*/
	/* Protected Methods
	-------------------------------*/
	/* Private Methods
	-------------------------------*/
}