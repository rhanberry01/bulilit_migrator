<?php
defined('BASEPATH') OR exit('No direct script access allowed');
ini_set('MAX_EXECUTION_TIME', -1);
ini_set('mssql.connect_timeout',0);
ini_set('mssql.timeout',0);
set_time_limit(0);  
ini_set('mssql.textlimit',2147483647);
ini_set('mssql.textsize',2147483647);
ini_set('memory_limit', -1);

class Main_model extends CI_Model {
 
	public function getData(){
		$this->db = $this->load->database("branch_nova", true);
		$this->db->where("throw !=", 1);
		$this->db->order_by("id");
		// eto ung ichchange sa main to branch
		$this->db->where("branch_code", "PLY");
		$result = $this->db->get("branch_updates");
		$result = $result->result_array();
		return $result;
	}

		public function execute($details){
			
			$this->db = $this->load->database("branch_nova", true);
			$stmt = $details["sql_statement"];
			$stmt = str_replace('True', '1',$stmt);
			$stmt = str_replace('False', '0',$stmt);
			
			if($this->db->query($stmt)) {
				echo $stmt.PHP_EOL;
				$this->db->where("id", $details["id"]);
				$this->db->update("branch_updates", array("throw" => 1));
			}
		}
    

}