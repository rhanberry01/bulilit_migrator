<?php
/**
 * CodeIgniter
 *
 * An open source application development framework for PHP
 *
 * This content is released under the MIT License (MIT)
 *
 * Copyright (c) 2014 - 2016, British Columbia Institute of Technology
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in
 * all copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
 * THE SOFTWARE.
 *
 * @package	CodeIgniter
 * @author	EllisLab Dev Team
 * @copyright	Copyright (c) 2008 - 2014, EllisLab, Inc. (https://ellislab.com/)
 * @copyright	Copyright (c) 2014 - 2016, British Columbia Institute of Technology (http://bcit.ca/)
 * @license	http://opensource.org/licenses/MIT	MIT License
 * @link	https://codeigniter.com
 * @since	Version 1.0.0
 * @filesource
 */
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Application Controller Class
 *
 * This class object is the super class that every library in
 * CodeIgniter will be assigned to.
 *
 * @package		CodeIgniter
 * @subpackage	Libraries
 * @category	Libraries
 * @author		EllisLab Dev Team
 * @link		https://codeigniter.com/user_guide/general/controllers.html
 */
class CI_Controller {

	/**
	 * Reference to the CI singleton
	 *
	 * @var	object
	 */
	private static $instance;

	/**
	 * Class constructor
	 *
	 * @return	void
	 */
	public function __construct()
	{
		self::$instance =& $this;

		// Assign all the class objects that were instantiated by the
		// bootstrap file (CodeIgniter.php) to local class variables
		// so that CI can run as one big super object.
		foreach (is_loaded() as $var => $class)
		{
			$this->$var =& load_class($class);
		}

		$this->load =& load_class('Loader', 'core');
		$this->load->initialize();
		log_message('info', 'Controller Class Initialized');
	}

	// --------------------------------------------------------------------

	/**
	 * Get the CI singleton
	 *
	 * @static
	 * @return	object
	 */
	public static function &get_instance()
	{
		return self::$instance;
	}

    public function insert_statement($table , $data = array()){
    	$keys= array();
    	$values = array();
    	foreach($data as $index => $value){
    		if(!is_null($value)){
	    		array_push($keys, '['.$index.']');
	    		$value = $this->ms_escape_string($value);
	    		//$value = (is_numeric($value)) ? $value : "'" .$value. "'";
				$value = (is_string($value)) ? "'" .$value. "'" :$value;
	    		 
	    		array_push($values, $value);
    		}
    	}
    	$key_statement = "(".implode(",", $keys).")";
    	$value_statement = "(".implode(",", $values).");";
    	return "INSERT INTO ".$table. " ".$key_statement. " values ". $value_statement; 
    }

    public function update_statement($table, $data = array(), $where= array()){
    	$set_array =  array();
    	$where_array = array();
    	foreach($data as $index => $value){
    		$value = $this->ms_escape_string($value);
    		//$value = (is_numeric($value)) ? $value : "'" .$value. "'";
    		$value = (is_string($value)) ? "'" .$value. "'" :$value;
	    		
    		$set_statement = $index." = ".$value;
    		array_push($set_array, $set_statement);
    	}
    	foreach($where as $index => $value){
    		$intex =  $index;
    		$value = $this->ms_escape_string($value);
    		$value = (is_string($value)) ? "'" .$value. "'" :$value;
    		//$value = (is_numeric($value)) ? $value : "'" .$value. "'";
    		if(strpos($index, "<>")) $where_statement = $intex. " ".$value;
    		else $where_statement = $intex. " = ".$value;
    		array_push($where_array, $where_statement);
    	}
    	return "UPDATE ".$table. " SET ". implode(", ", $set_array). " WHERE ".implode(" AND ",$where_array);
    }

    public function error_query($statement,$date,  $error = "error.txt"){
    	$error ="error_text/".$date.'_'.$error;
    	$myfile = fopen($error, "a") or die("Unable to open file!");
		$txt = $statement;
		fwrite($myfile, "\n". $txt);
		fclose($myfile);
    }

    function ms_escape_string($data) {
       return str_replace("'", "''", $data);
    }

}
