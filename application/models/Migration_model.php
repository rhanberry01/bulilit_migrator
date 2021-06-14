<?php
defined('BASEPATH') OR exit('No direct script access allowed');


class Migration_model extends CI_Model {
 
    public function insert_statement($statement,   $date_added, $table = "queue"){
        $this->db->insert($table, array("sql_statement" => $statement , "date_added" => $date_added));
        return $this->db->insert_id();
    }

    public function bulk_insert_statement($data, $table = "queue"){
        if(!empty($data)) $this->db->insert_batch($table, $data);
    }

     public function select_count_ms_table($table, $from = null,$to = null, $date = null, $where = array(), $where_statement = null){
     	$this->db = $this->load->database("branch_nova", true);
        $this->free_up();
        foreach($where as $index => $value) $this->db->where($index, $value);
        if($where_statement) $this->db->where($where_statement, null, false);
        if($from != null) $this->db->where($from .' >=', $date);
        $date = date('Y/m/d',strtotime($date . "+1 days"));
        //$date = date('Y/m/d H:i:s',strtotime($date . "-1 seconds"));
        if($from != null) $this->db->where($to .' <', $date);
        $this->db->select("count(1) as row_counts");
        $query = $this->db->get($table);
        $row = $query->result_array();
        return $row[0];
     }


    public function select_ms_table($table, $id, $count_from, $count_to,  $from = null , $to = null, $date = null, $where_statement = null){
       $this->db = $this->load->database("branch_nova", true);
        $this->free_up();
        $where = "";
        $date_to = date('Y/m/d',strtotime($date . "+1 days"));
       // $date_to = date('Y/m/d H:i:s',strtotime($date_to . "-1 seconds"));
        if($from != null) $where = "where ". $from ." >= '".$date."' AND ".$to." < '".$date_to."'";
        if($where_statement)
            $where = ($where != "") ?  $where .' AND '. $where_statement : " WHERE ". $where_statement;
        $where = trim($where);
        $sql = "
    		select 
    			* 
    		FROM( 
    			SELECT 
    					*, 
    					ROW_NUMBER() over (ORDER BY ".$id." ) as ct 
    			from ".$table." ".$where."
    		) sub where ct > ".$count_from."  and ct <= ".$count_to."
    	";
       $sql = trim($sql);
       $query = $this->db->query($sql);
        return $query->result_array();
     }

    public function select_where($table, $where = array(), $db = "branch_nova", $limit = false,$id =null){
        $this->db = $this->load->database($db, true);
        if(!empty($where))
            foreach($where as $where => $value) $this->db->where($where, $value);
        if($limit) $this->db->limit(10000);
        if($id != null) $this->db->order_by($id);
        $query = $this->db->get($table);
        if($query) return $query->result_array();
        return array();
    }

   
    public function free_up(){
         $this->db->query("DBCC FREEPROCCACHE WITH NO_INFOMSGS;");
    }
    public function load_db($dbname){
        $this->db = $this->load->database($dbname, TRUE);
    }

    public function execute_queue($query , $id, $table = "main_nova"){
            $this->db = $this->load->database($table, TRUE);
            if($this->db->conn_id){
                if($this->db->query($query)){
                    echo $query.PHP_EOL;
                    $this->update_status($id);
                    return true;       
                } else return false;
            } return false;
       
    } 
    
    public function update_status($id, $table = "queue"){
        $this->db = $this->load->database("default", true);
        $this->db->where("id", $id);
        $this->db->update("queue", array("status" => 1));
    }


    public function update_status2($id){
        $this->db = $this->load->database("default", true);
        $this->db->where("id", $id);
        $this->db->update("queue2", array("status" => 1));
    }

    public function execute_queue2($query , $id, $table = "main_nova_mysql"){
            $this->db = $this->load->database($table, TRUE);
            $db = $this->load->database("main_nova", TRUE);
            if($this->db->conn_id && $db->conn_id ){
                if($this->db->query($query) && $db->query($query)){
                    echo $query.PHP_EOL;
                    $this->update_status2($id);
                    return true;       
                } else return false;
            } return false;
    } 

     public function select_latest_file($action = true, $id = null){
        $this->db = $this->load->database("default", true);
        $this->db->order_by("id", "desc");
        if($id!=null) $this->db->where("id", $id);
        $query = $this->db->get("file_offline");
        $row = $query->result_array();
        if($action == true){
            if(!empty($row)) return $row[0];
            else return array(); 
        } else return $row;
    }

    public function select_off_status($limit = null){
        $this->db = $this->load->database("default", true);
        $this->db->where("status" , 0);
        if($limit != null) $this->db->limit($limit);
        $result = $this->db->get("queue");
        return $result->result_array();
    }

     public function update_off_status($limit = null){
        $this->db = $this->load->database("default", true);
        $this->db->where("status", 0);
        $this->db->update("queue", array("status" => 1));
    }

    

    public function insert_file($file_name, $count){
        $this->db = $this->load->database("default", true);
        $this->db->insert("file_offline", array("total_count" => $count, "filename" => $file_name));
    }

    public function update_count($id){
        $file = $this->select_latest_file(true, $id);
        $count = $file["inserted"] + 1;
        $this->db->where("id", $id);
        $this->db->update("file_offline", array("inserted" => $count));
    }

    public function select_product(){
        $this->db = $this->load->database("branch_nova", true);
        $this->db->limit(10);
        $query = $this->db->get("Products");
        return $query->result_array();
    }

	  public function update_status_local($id){
        $this->db->where("id", $id);
        $this->db->update("queue", array("status" => 1));
    }

	public function ping($host,$port=1433,$timeout=5)
	{
			$fsock = fsockopen($host, $port, $errno, $errstr, $timeout);
			if ( ! $fsock )
			{
					return FALSE;
			}
			else
			{
					return TRUE;
			}
	}

     public function select_count_queue_off($where = array(), $table = "queue"){
        $this->db = $this->load->database("default", true);
        if(!empty($where))
            foreach($where as $where => $value) $this->db->where($where, $value);
        $this->db->select("count(1) as row_count");
        $count = $this->db->get($table);
        $row = $count->result_array();
        echo $this->db->last_query().PHP_EOL;
        if($row[0]["row_count"] > 0 ) return true;
        else return false;
    }

    public function delete_status_zero($date){
        $this->db= $this->load->database("default", TRUE);
        $this->db->query("delete from queue where status = 1");
		$this->db= $this->load->database("default", TRUE);
		$this->db->where("date_added", $date);
		$this->db->select("count(1) as row_count"); 
		$query = $this->db->get("queue");
		$result = $query->result_array();
		if($result[0]["row_count"] > 0 ){
			$this->db->where("date_added", $date);
			$this->db->delete("queue");
		}
    }

}