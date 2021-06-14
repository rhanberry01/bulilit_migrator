<?php
if ( ! defined('BASEPATH')) exit('No direct script access allowed');
ini_set('MAX_EXECUTION_TIME', -1);
ini_set('mssql.connect_timeout',0);
ini_set('mssql.timeout',0);
set_time_limit(0);  
ini_set('memory_limit', -1);

//client_buffer_max_kb_size = '50240'
//sqlsrv.ClientBufferMaxKBSize = 50240

class Welcome extends CI_Controller {
	public $tables = array(), $relational = array(), $updates, $dividend = 0, $real_time_updates=array();
	public function __construct(){
		date_default_timezone_set('Asia/Manila');
		parent::__construct();
		$this->load->model("Migration_model");
		$this->tables = array(
			"Movements" => array(
				"from" => "PostedDate",
				"to" => "PostedDate",
				"id" => "MovementID",
				"identity" => 1,
				"where" => "status = 2",
				"child" => array(
					"MovementLine" => array( "except" => "LineID", "main_id" => "MovementID" ),
					"MovementLineSerialNumbers" => array( "except" => "LineID", "main_id" => "MovementID" ),
					"MovementSubTotalDiscounts"=> array( "except" => "LineID", "main_id" => "MovementID" ),
					"MovementLineDiscounts" => array( "except" => "LineID", "main_id" => "MovementID" ),
					"MovementSubTotalDiscountsDescription" => array(  "main_id" => "MovementID" )
				)
			),
			"Receiving" => array(
				"from" => "PostedDate",
				"to" => "PostedDate",
				"id" => "ReceivingID",
				"where" => "status = 2",
				"identity" => 1,
				"child" => array(
					"ReceivingLine" => array( "except" => "LineID", "main_id" => "ReceivingID" ),
					"ReceivingLineDiscounts" => array( "except" => "LineID", "main_id" => "ReceivingID" ),
					"ReceivingSubTotalDiscounts"=> array( "except" => "LineID", "main_id" => "ReceivingID" ),
					"ReceivingSubTotalDiscountsDescription" => array( "except" => "LineID", "main_id" => "ReceivingID" ),
					"ReceivedProducts" => array(  "main_id" => "ReceivingID" )
				)
			),
			"ProductHistory" => array(
				"id" => "LineID",
				"from" => "DatePosted",
				"to" => "DatePosted",
				"except" => true
			),
			"finishedPayments" => array(
				"id" => "TransactionNo",
				"from" => "LogDate",
				"to" => "LogDate"
			),
			"finishedTransaction" => array(
				"id" => "TransactionNo",
				"from" => "LogDate",
				"to" => "LogDate"
			),
			"finishedSales" => array(
				"id" => "LineID",
				"from" => "LogDate",
				"to" => "LogDate",
				"except" => true
			)
		);

		$this->updates = array(
			"Products" => array(
				"id" => "ProductID",
				//"from" => "CONVERT(VARCHAR(10),[LastDateModified], 111)",
				//"to" => "CONVERT(VARCHAR(10),[LastDateModified], 111)",
				"where" => array(
					"[SellingArea]",
					"[StockRoom]",
					"[Damaged]",
					"[LastSellingDate]",
					//"[LastDateModified]",
					"[todatefinishedsales]",
					"[CostOfSales]"
				)
			),
			"Counters" => array(
				"id" => "TransactionTypeCode",
				"where" => array(
					"Counter"
				),
				"where_custom" => array(
					"TransactionTypeCode <>" => "PO"
				)
			)
		);
		$this->dividend = 4;

        $this->real_time_updates = array(
			"products" => array(
				"id" => "ProductID",
				"where" => array(
					"SellingArea"
				),
				"where_statement" => "LevelField1Code = '9092' OR LevelField1Code = '10021'"
			)
		);
		
		
	}
	
	public function insert_table()
	{
		$data = array("first_name" => "sample_name '", "second_name" => "sample_second");
		$query = $this->insert_statement("sample_table", $data);
		$id = $this->Migration_model->insert_statement($query);
		if(!$id) $this->error_query($query);
		
	}

	public function update_table(){
		$where = array(
			"id" => 15,
			"name" => "tests's"
		);

		$set = array(
			"test_name" => "test,'",
			"array_name" => "Word"
		);

		$query = $this->update_statement("sample_table", $set, $where);
		$id = $this->Migration_model->insert_statement($query);
		if(!$id) $this->error_query($query);		
	}


	public function start_process( $date_yesterday = null ){
		
		if($date_yesterday==null){
		$date =  date("Y-m-d H:i:s").PHP_EOL;
		$date_yesterday = date("Y-m-d" , strtotime('-1 day', strtotime($date)));
		}
		echo $date_yesterday.PHP_EOL; 
		$this->Migration_model->delete_status_zero($date_yesterday);

		$this->update_main($date_yesterday);
		$this->select_ms($date_yesterday);
		echo date("Y-m-d H:i:s").PHP_EOL;
	}
	
	
	public function select_ms($date_yesterday){
		foreach($this->tables as $i => $tbl){
		     $where_statement = ( isset($tbl["where"]) ) ? $tbl["where"] : null;
			 $row = $this->Migration_model->select_count_ms_table($i, $tbl["from"] ,$tbl["to"], $date_yesterday, array(), $where_statement);
			 $row_count = $row["row_counts"];
			 echo $i.'-'.$row_count.PHP_EOL; 
			 if($row_count == 0) continue; 
			 $increment = ceil($row_count/$this->dividend);
			 $total = $increment * $this->dividend;
			 for($x = $increment; $x<= $total; $x = $x+$increment){
			 	$from = $x - $increment;
			 	$to = $x;
			    $data = $this->Migration_model->select_ms_table($i, $tbl["id"], $from, $to, $tbl["from"],$tbl["to"],  $date_yesterday , $where_statement);
			    $this->Migration_model->load_db("default");
			    $main_insert = array();
			    foreach($data as $record) {
			    	unset($record["ct"]);
			    	if( isset($tbl["except"]) ) unset($record[$tbl["id"]]); 
			    	$insert_statement =  $this->insert_statement($i, $record);
			    	if(isset($tbl["identity"])) 
			    		if($tbl["identity"] == 1) $insert_statement = 'SET IDENTITY_INSERT '.$i.' ON ' .$insert_statement. ' SET IDENTITY_INSERT '.$i.' OFF;'; 
			    	//$result_insert = $this->Migration_model->insert_statement($insert_statement, $date_yesterday);
			    	$main_insert[] = array(
			    		"sql_statement" => $insert_statement,
			    		"date_added" => $date_yesterday
			    	);
			    	//if(!$result_insert) $this->error_query($insert_statement, $date_yesterday);
			    }
			    $this->Migration_model->bulk_insert_statement($main_insert);

			    if(isset($tbl["child"])){
			    	foreach($data as $record){
			    		unset($record["ct"]);
			    		$array_insert = array();
			    		foreach($tbl["child"] as $child_index => $child_value){
			    			$foreign_id = $record[$tbl["id"]];
			    			$where = array(
			    				$child_value["main_id"] => $foreign_id
			    			);
			    			$child_query = $this->Migration_model->select_where($child_index, $where);
			    			foreach($child_query as $child){
			    				if(isset($child_value["except"])) unset($child[$child_value["except"]]);
			    				$insert_statement =  $this->insert_statement($child_index, $child);
			    				//array_push($array_insert, $insert_statement);
			    				$array_insert[] = array(
			    					"sql_statement" => $insert_statement,
			    					"date_added" => $date_yesterday
			    				);
			    			}
			    		}
			    		if(!empty($array_insert)){
			    			$this->Migration_model->load_db("default");
			    			$this->Migration_model->bulk_insert_statement($array_insert);
						}
			    	}
			    	
			    }



			 }
		   
		}

	}

	public function update_main($date_yesterday){
		foreach($this->updates as $i => $up){
				$row = (isset($up["from"])) ?  $this->Migration_model->select_count_ms_table($i, $up["from"] ,$up["to"], $date_yesterday) : $this->Migration_model->select_count_ms_table($i);
				$row_count = $row["row_counts"];
				echo  $i  .'-'.$row_count.PHP_EOL;
				if($row_count  == 0) continue;
				$increment = ceil($row_count/$this->dividend);
				$total = $increment *$this->dividend ;
					 for($x = $increment; $x<= $total; $x = $x+$increment){
					 	$from = $x - $increment;
					 	$to = $x;
						   $data = (isset($up["from"])) ? $this->Migration_model->select_ms_table($i, $up["id"], $from, $to, $up["from"] ,$up["to"], $date_yesterday) :  $this->Migration_model->select_ms_table($i, $up["id"], $from, $to);
						   
						   $this->Migration_model->load_db("default");
						   $products = array();
						   foreach($data as $record) {
						   	        $set = array();
						   			unset($record["ct"]);
						    		$where = array(
										$up["id"] => $record[$up["id"]]
									);
									if(isset($up["where"])) {
										foreach($up["where"] as $where_field) {
											$index_where = $where_field;
											$index_where = str_replace("[","",$index_where);
											$index_where = str_replace("]","",$index_where);
											$set[$where_field] = $record[$index_where];
										}
									}
									if(isset($up["where_custom"])) foreach($up["where_custom"] as $ind => $v) $where[$ind] = $v;
									$query = $this->update_statement($i, $set, $where);
									$products[] = array(
										"sql_statement" => $query,
										"date_added" => $date_yesterday
									);
						    		//$result_insert = $this->Migration_model->insert_statement($query, $date_yesterday);
									//if(!$result_insert) $this->error_query($insert_statement, $date_yesterday);
									//else echo date("Y-m-d H:i:s").PHP_EOL;
						 }
						 $this->Migration_model->bulk_insert_statement($products);
					 }
		}
	}

	public function real_time_updates($date_yesterday = null){
		if($date_yesterday==null){
			$date =  date("Y-m-d H:i:s").PHP_EOL;
			$date_yesterday = date("Y-m-d" , strtotime('-1 day', strtotime($date)));
		}
        foreach($this->real_time_updates as $i => $up){
        	    $where_state = (isset($up["where_statement"])) ? $up["where_statement"] : null;
        	    $row = (isset($up["from"])) ?  $this->Migration_model->select_count_ms_table($i, $up["from"] ,$up["to"], $date_yesterday) : $this->Migration_model->select_count_ms_table($i, null, null, null, array(), $where_state );
				$row_count = $row["row_counts"];
				echo  $i  .'-'.$row_count.PHP_EOL; 
				if($row_count  == 0) continue;
				$increment = ceil($row_count/$this->dividend);
				$total = $increment *$this->dividend ;
					 for($x = $increment; $x<= $total; $x = $x+$increment){
					 	$from = $x - $increment;
					 	$to = $x;
						   $data = (isset($up["from"])) ? $this->Migration_model->select_ms_table($i, $up["id"], $from, $to, $up["from"] ,$up["to"], $date_yesterday) :  $this->Migration_model->select_ms_table($i, $up["id"], $from, $to, null, null,null, $where_state);
						   $this->Migration_model->load_db("default");
						   $products = array();
						   foreach($data as $record) {
						   	        $set = array();
						   			unset($record["ct"]);
						    		$where = array(
										$up["id"] => $record[$up["id"]]
									);
									if(isset($up["where"])) {
										foreach($up["where"] as $where_field) {
											$index_where = $where_field;
											$index_where = str_replace("[","",$index_where);
											$index_where = str_replace("]","",$index_where);
											$set[$where_field] = $record[$index_where];
										}
									}
									if(isset($up["where_custom"])) foreach($up["where_custom"] as $ind => $v) $where[$ind] = $v;
									$query = $this->update_statement($i, $set, $where);
									$products[] = array(
										"sql_statement" => $query,
										"date_added" => $date_yesterday
									);
						 }
						 $this->Migration_model->bulk_insert_statement($products, 'queue2');
					 }
		}
	}

	public function branch_to_main($id_check = null){
			$bool = true;
			ini_set("display_error", 0);
			while($bool){
				$where = array("status" => 0);
				if($id_check == 2) $where["(id%2)"] = 0;
				else if($id_check == 1) $where["(id%2)"] =1;
				$query = $this->Migration_model->select_where("queue", $where, "default", true, "id");
				foreach( $query as $i => $record ) { 
					$res = $this->Migration_model->execute_queue($record["sql_statement"] ,$record["id"]); 
					if($res == false)  break;
				}
				$bool = $this->Migration_model->select_count_queue_off($where);
			}
			
	}

	public function real_time_branch_to_main($id_check = null){
			$bool = true;
			ini_set("display_error", 0);
			while($bool){
				$where = array("status" => 0);
				if($id_check == 2) $where["(id%2)"] = 0;
				else if($id_check == 1) $where["(id%2)"] =1;
				$query = $this->Migration_model->select_where("queue2", $where, "default", true, "id");
				foreach( $query as $i => $record ) { 
					$res = $this->Migration_model->execute_queue2($record["sql_statement"] ,$record["id"] , 'main_nova_mysql'); 
					if($res == false) break;
				}
				$bool = $this->Migration_model->select_count_queue_off($where, "queue2");
			}
			
	}

 public function on_off($action=null){
    	$data = array();
    	$message = null;
    	if($action == "creating")
    	{
    		$latest_file = $this->Migration_model->select_latest_file();
    		$access = false;
    		if(empty($latest_file)) $access = true;
    		else if($access["total_count"] == $access["inserted"]) $access = true;
    		if($access){
	    		$file_name = date("Y_m_d_H_i_s").BRANCH_PROCESSING;
	    		$query = $this->Migration_model->select_off_status();
	    		if(!empty($query))
	    		{
	    		 $count = count($query);
	    		 $this->Migration_model->insert_file($file_name, $count);
	    		} else $this->session->set_userdata("message","Cannot Create File, No Records Found..!");

    		} 
    		redirect(base_url("welcome/on_off"));
    	}
    	$check_latest_file = $this->Migration_model->select_latest_file();
    	$data = $this->can_create($check_latest_file);
    	$data["table"] = $this->latest_sql_file();
    	if($this->session->userdata("message")) {
    		$data["message"] = $this->session->userdata("message");
    		$this->session->unset_userdata("message");
    	}
    	$this->load->view("on_off", $data);
    }

    public function can_create($latest_file){
    	$array = array();
    	$array["script"] = "";
    	if(empty($latest_file)) {
    		$array["download"] = "";
    		$array["create"] = '<a href = "'.base_url("welcome/on_off/creating").'" class = "btn-sm btn-primary"> <i class="fa fa-fw fa-wrench"></i> </a>';
    		$array["check"] = 'checked';
    	}
    	else if($latest_file["total_count"] == $latest_file["inserted"]) { 
    		$array["download"] = " <b>".$latest_file["filename"].'</b>.txt <a href = "'.base_url('files/'.$latest_file["filename"].'.txt') .'" class = "btn-sm btn-primary"> <i class="fa fa-fw fa-download"></i> </a>
               </div>';
           	$array["create"] = '<a href = "'.base_url("welcome/on_off/creating").'" class = "btn-sm btn-primary"> <i class="fa fa-fw fa-wrench"></i> </a>';
  			$array["check"] = 'checked';
  		}
        else { 
        	if($latest_file["inserted"] == 0) $computation = 0;
        	else $computation = round(($latest_file["inserted"] / $latest_file["total_count"] ) * 100,2) ;
        	$array["create"] = ' <label id = "percentage">'.$computation.'</label>%';
        	if($computation == 100) $array["download"] = '<span class="label label-success">Waiting until page reloaded</span> <i class="fa fa-refresh fa-spin"></i>';
        	else $array["download"] = '<span class="label label-success">Generating</span> <i class="fa fa-refresh fa-spin"></i>';
        	$array["check"] = '';	
        	$base_url = base_url("welcome/get_computation_latest");
        	$array["script"] = '
        			$(function () {
        				setInterval(function() {
 
								$.get( "'.$base_url.'", function( data ) {
									if(data.reload)	location.reload();
									else $("#percentage").html(data.percentage);
		  						});

						}, 5000);
					});
        	';
 		}
    	return $array;
	}

	public function latest_sql_file(){

		$rows = "";
		$files = $this->Migration_model->select_latest_file(false);
		foreach($files as $file)
		{
			if($file["inserted"] == $file["total_count"])
			{
				$url = '<a href = "'.base_url('files/'.$file["filename"].'.txt') .'" class = "btn-sm btn-primary"> <i class="fa fa-fw fa-download"></i> </a>';
				$rows .="<tr>
							<td>".$file["filename"]."</td>
							<td>".$file["date_added"]."</td>
							<td>".$url."</td>
				</tr>";
			}
		}

		return '

            <!-- /.box-header -->
            <div class="box-body">
              <table class="table table-bordered">
                <tbody>
                <tr>
                  <th>Filename</th>
                  <th>Date Created</th>
                  <th>Download</th>
                </tr>
                '.$rows.'
              </tbody></table>
            </div>
           
          </div>';
	}
	public function get_computation_latest()
	{
		$reload = false;
		$latest_file = $this->Migration_model->select_latest_file();
		if($latest_file["inserted"] == 0) $computation = 0;
        else $computation = ($latest_file["inserted"] / $latest_file["total_count"] ) * 100 ;
        if($computation == 100) $reload = true;
        $computation = round($computation);
        header("Content-Type: application/json");
		echo json_encode(array("percentage"=>$computation, "reload" => $reload));
	}

	public function create_file(){
		 while(true){
		 	$file =$this->Migration_model->select_latest_file();
			 if(!empty($file) ){
		         if($file["inserted"] != $file["total_count"]){
		         	$filename ="files/".$file["filename"].".txt";
		         	$total_count = $file["total_count"] - $file["inserted"];
		            $queries =$this->Migration_model->select_off_status($total_count);
		            $fh = fopen($filename, 'a') or die("can't open file");
		            foreach($queries as  $query){
		            	fwrite($fh, $query["sql_statement"].PHP_EOL);
						$this->Migration_model->update_status($query["id"]);
						$this->Migration_model->update_count($file["id"]);
		            }
		            fclose($fh);
		         }
	         }
	         sleep(2);
        }
	}
   
	public function get_offline_query(){
			$data = $this->Migration_model->select_off_status();
			$date= SRS_MAIN_BRANCH."_backup_".date("Y_m_d_H_i_s");
			$filename ="files/".$date.".txt";
			$sql_statements = "";
			if(count($data) > 0){
				$fh = fopen($filename, 'a') or die("can't open file");
				$id = array();
				$this->db = $this->load->database("default", true);
				$text = "";
				foreach($data as $row){
					$text .=  $row["sql_statement"].PHP_EOL;
				}
				fwrite($fh, $text);
				$this->Migration_model->update_off_status();
				fclose($fh);
			}
	   }

	public function check_setup_details(){
		echo SRS_MAIN_BRANCH. " " . SRS_MAIN_DATABASE. " ". SRS_BRANCH_DATABASE;
		sleep(5);
	}

	public function check_setup_details_mysql(){
		echo MYSQL_NOVA. " " . MYSQL_PASS. " ". MYSQL_NOVA_DATABASE;
		sleep(5);
	}

	public function get_fresh(){
		


	}
	
}
