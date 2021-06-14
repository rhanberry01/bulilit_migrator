<?php
defined('BASEPATH') OR exit('No direct script access allowed');
ini_set('MAX_EXECUTION_TIME', -1);
ini_set('mssql.connect_timeout',0);
ini_set('mssql.timeout',0);
ini_set('mssql.textlimit',2147483647);
ini_set('mssql.textsize',2147483647);
set_time_limit(0);  
ini_set('memory_limit', -1);
class Datacenter_model extends CI_Model {

	public function getBranchesUpdate($branch_code){
		$this->load_db();
		//$array = array("SRN", "SRM");
		$this->db->where("throw !=", 1);
		$this->db->where("branch_code", $branch_code);
		$this->db->where("date_added >", "2017-07-23");
		//if(count($array)  == 1) $this->db->where("branch_code", $array[0]);
		//else $this->db->where_in("branch_code", $array);
		$this->db->select("id, branch_code, throw, cast(sql_statement as varchar(MAX)) as sql_statement");
		$result = $this->db->get("branch_updates");
		$result = $result->result_array();
		return $result;
	}

	public function distinct_branch(){
		$this->load_db();
		$this->db->where("throw !=", 1);
		$this->db->select("distinct(branch_code) as branch_code");
		$result = $this->db->get("branch_updates");
		$result = $result->result_array();
		$this->db->where("throw !=", 1);
		$this->db->select("distinct(branch_code) as branch_code");
		$result2 = $this->db->get("barcode_update");
		$result2 = $result2->result_array();
		$result = array_merge($result2, $result);
		return $result;
	}

	public function branch_details($branch_code){
		$this->load_db();
		$this->db->where("branchcode", $branch_code);
		$result = $this->db->get("branches");	
		$result = $result->result_array();
		return $result[0];
	}

	public function throw_line($data, $branch_details){
		$bool = $this->ping($branch_details["branchservername"]);
		if($bool){
			$id = $data["id"];
			$this->change_details($branch_details);
			unset($data["date_added"]);
			if($this->db->insert("branch_updates", $data)){
				print_r($data);
				$this->load_db();
				$this->db->where("id", $id);
				$this->db->where("branch_code", $branch_details["branchcode"]);
				$this->db->update("branch_updates", array("throw" => 1));
				return true;
			}
		}
		return false;
	}

	public function change_details($details){
		$dsn = "mssql://".$details["branchserverusername"].":".$details["branchserverpassword"].'@'.$details["branchservername"]."/".$details["branchserverdatabasename"];
        $this->db= $this->load->database($dsn, true);
    }
}
