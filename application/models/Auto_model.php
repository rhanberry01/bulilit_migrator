<?php
defined('BASEPATH') OR exit('No direct script access allowed');


class Auto_model extends CI_Model {
    public $local_db, $customer_code = array();
    public function __construct(){
		parent::__construct();
		$this->local_db = "branch_nova";
		$this->main_db = "main_branch_mysql";
		$this->pricing_db = "pricing_db";
		$this->customer_code = $this->get_customer_code();
		$this->msdb = $this->load->database("branch_nova", true);
	}

		public function trans_begin(){
			$this->msdb->trans_begin();
		}

		public function trans_status(){
			$res = $this->msdb->trans_status();
			return $res;
		}

			public function trans_rollback(){
				$this->msdb->trans_rollback();
		}

		public function trans_commit(){
			$this->msdb->trans_commit();
		} 

		public function trans_complete(){
			$this->msdb->trans_complete();
		} 
		
		
		

	public function insert_ms_header($data = array()){ 
		$this->msdb = $this->load->database("branch_nova", true);
		$this->msdb->insert('Movements', $data);
  		 $insert_id = $this->msdb->insert_id();
		 return  $insert_id;
	 }

	 public function insert_ms_details($data = array()){ 
		$this->msdb->insert('MovementLine', $data);
  		 $insert_id = $this->msdb->insert_id();
		 return  $insert_id;
	 }

	 public function insert_phistory_details($data = array()){ 
		$this->msdb->insert('producthistory', $data);
  		 $insert_id = $this->msdb->insert_id();
		 return  $insert_id;
	 }
	 public function update_prodcuts($ProductID,$totalreturned){
		$sql = "UPDATE Products SET SellingArea = (SellingArea - ".$totalreturned.") where ProductID = ".$ProductID." ";			
		$res = $this->msdb->query($sql);
		return $res;
	 }

	 public function update_mov_header($movementid,$total){
		$sql = "UPDATE Movements SET NetTotal = $total where movementid = ".$movementid." ";			
		$res = $this->msdb->query($sql);
		return $res;
	 }

	 public function get_productid($barcode){
		$this->msdb = $this->load->database("branch_nova", true);
		$sql = "select TOP 1 ProductID,ProductCode,uom,qty from POS_Products where Barcode ='".$barcode."' ";				
		$res = $this->msdb->query($sql);
		$row = $res->row();
		return $row;
	 }
	 
	public function get_ms_ctr($code){
		$this->msdb = $this->load->database("branch_nova", true);
		$sql = "SELECT (counter + 1) as counter FROM [dbo].[Counters] where TransactionTypeCode ='".$code."'";				
		$res = $this->msdb->query($sql);
		$row = $res->row();
		return $row->counter;
	}	
	public function update_ms_ctr($code){
			$upd_sql = "UPDATE [dbo].[Counters] SET counter = (counter + 1)  where TransactionTypeCode ='".$code."'";
			$this->msdb->query($upd_sql);	
	}
	

	public function update_unprocessed_return($return_id,$header_id, $counter_mov){
        $branch_name = BRANCH_NAME;
         $this->db = $this->load->database("mysql_pos_db", true);
         $sql = "UPDATE `franchisee_return_header` SET is_ms_process =  1, OldTerminalNo =  '".$header_id."', OldTransactionNo = '".$counter_mov."'
		 where return_id in (".$return_id.") and CustomerName = '".$branch_name."' ";
         $res = $this->db->query($sql);
		 return  $res;
    }

	public function get_unprocessed_return(){
		$branch_name = BRANCH_NAME;
		 $this->db = $this->load->database("mysql_pos_db", true);
		$sql = "SELECT DateServed,CustomerName,NewTransactionNo,NewTerminalNo,GROUP_CONCAT(return_id) as return_id,is_ms_process 
		FROM `franchisee_return_header` 
		where DateServed is not null and NewTransactionNo is not null and CustomerName = '".$branch_name."'
		and is_ms_process  = 0 
		GROUP BY NewTerminalNo,NewTransactionNo
		ORDER BY DateServed";

		$res = $this->db->query($sql);
	    $res = $res->result_array();
	    return $res;
	}

	public function get_unprocessed_return_details($return_id,$DateServed){
		$branch_name = BRANCH_NAME;
		$this->db = $this->load->database("mysql_pos_db", true);
		$sql = "select id,Barcode,ProductName,Srp,ReturnedQty from franchisee_return where return_header_id in(".$return_id.")";
		//$sql = "select fr.id,fr.Barcode,fr.ProductName,fr.Srp,fr.ReturnedQty,fr.return_header_id,tfs.Barcode,tfs.UOM,tfs.Qty,tfs.Packing
		//	from franchisee_return  as fr
		//	LEFT JOIN tfinishedsales as tfs on fr.return_header_id = tfs.OldTransactionNo and fr.Barcode = tfs.Barcode 
		//	where cast(tfs.logdate as date) = '".$DateServed."' and  fr.return_header_id in(".$return_id.")  and tfs.`ReturnRemarks` = '".$branch_name."' and tfs.`Return` = 1";
		$res = $this->db->query($sql);
	    $res = $res->result_array();
	    return $res;

	}




	

	public function get_purchases_res($sales_date = null,$aria_db = null,$type = null, $account = null,$type_no = null){

        $this->ddb = $this->load->database($aria_db, true);
        $sql = "SELECT SUM(ABS(amount))+0 as gl_amount FROM  0_gl_trans 
        where tran_date = '".$sales_date."' and type ='".$type."' and account IN (".$account.") and type_no ='".$type_no."' ";
       $result = $this->ddb->query($sql);
        $result = $result->row();
        if($result){
            return  $result->gl_amount;
        }else{
            return  0;
        }

    }

	 public function get_ms_returns_franchisee($ms_db){
        $end_date = date('Y-m-d');
        $past_30days = date('Y-m-d', strtotime('-30 days'));
		
		$this->ddb = $this->load->database($ms_db, true);
		
		$sql = "select 
			(MovementNo*1) as type_no,cast(m.posteddate as date) as date_,SUM(ROUND((ml.qty*unitcost),4)) as tot_amt, SourceInvoiceNo as OrNum ,Remarks 
			from Movements as m
			LEFT JOIN MovementLine as ml
			on m.MovementID = ml.MovementID 
			where MovementCode = 'R2SSA'
			and cast(m.posteddate as date) >='".$past_30days."' and cast(m.posteddate as date) <='". $end_date."'
			GROUP BY cast(m.posteddate as date),MovementNo,SourceInvoiceNo,Remarks ORDER BY MovementNo
			";
		$result = $this->ddb->query($sql);
		$result = $result->result();
		return  $result;
	 }


    public function get_ms_purchases_franchisee($ms_db){
        $end_date = date('Y-m-d');
        $past_30days = date('Y-m-d', strtotime('-30 days'));
      

       $this->ddb = $this->load->database($ms_db, true);
       $sql = "select 
       (MovementNo*1) as type_no,cast(m.posteddate as date) as date_,SUM(ROUND((ml.qty*unitcost),4)) as tot_amt, SourceInvoiceNo as OrNum
       from Movements as m
      LEFT JOIN MovementLine as ml
      on m.MovementID = ml.MovementID 
      where MovementCode = 'STI'
      and cast(m.posteddate as date) >='".$past_30days."' and cast(m.posteddate as date) <='". $end_date."'
      GROUP BY cast(m.posteddate as date),MovementNo,SourceInvoiceNo ORDER BY MovementNo";

       echo  $sql;
       $result = $this->ddb->query($sql);
       $result = $result->result();
       return  $result;

    }

    public function get_ms_sales_franchisee($ms_db){
        $end_date = date('Y-m-d', strtotime('-1 days'));
        $past_30days = date('Y-m-d', strtotime('-32 days'));
      

       $this->ddb = $this->load->database($ms_db, true);
       $sql = "SELECT cast (LogDate as Date) as LogDate ,(SUM(ft.GrandTotal)-SUM(ft.ReturnSubtotal)) as total
               FROM FinishedTransaction as ft
               WHERE LogDate >= '".$past_30days."' and LogDate<= '".$end_date."'
               AND Voided='0'
               group by LogDate
               order by LogDate";

       $result = $this->ddb->query($sql);
       $result = $result->result();
       return  $result;
    }

	public function insert_gl($aria_db = null,$type = null, $type_no = null, $sales_date = null,$memo =null, $amount = null,$account = null,$person_type_id=3,$person_id=0){
        $this->ddb = $this->load->database($aria_db, true);
        $sql = "INSERT INTO 0_gl_trans (type, type_no, tran_date,account,amount,memo_,person_type_id,person_id) VALUES ('".$type."', ".$type_no.", '".$sales_date."','".$account."',".-$amount.",'".$memo."','".$person_type_id."','".$person_id."')";
        //return $sql;
		$result = $this->ddb->query($sql);
        if($result){
            return  true;
        }else{
            return false;
        }
    }

	public function add_refs($aria_db = null,$type = null, $max_type_no = null, $refs = null){
        $this->ddb = $this->load->database($aria_db, true);
        $sql = "INSERT INTO 0_comments (id,type,reference) VALUES (".$max_type_no.",".$type.",'".$refs."')";
        $result = $this->ddb->query($sql);
        if($result){
            return  true;
        }else{
            return false;
        }


     }


	 public function add_audit_trail($aria_db,$trans_type, $trans_no, $trans_date, $descr='')
	 {
		 
		 $ip = $this->input->ip_address();
 
		 $this->ddb = $this->load->database($aria_db, true);
		 $sql = "INSERT INTO 0_audit_trail"
				 . " (type, trans_no, user, fiscal_year, gl_date, description, gl_seq, remote_address)
				 VALUES(".$trans_type.", ".$trans_no.","
				 .'1'. ","
				 .'1'.","
				 . "'".date("Y-m-d h:i:sa")."',"
				 . $descr. ", NULL,"
				 . $ip. ")";
		 $result = $this->ddb->query($sql);
		 if($result){
			 return  true;
		 }else{
			 return false;
		 }
 
	 }

	public function add_comments($aria_db = null,$type = null, $max_type_no = null, $sales_date = null, $memo = null){
        $this->ddb = $this->load->database($aria_db, true);
        $sql = "INSERT INTO 0_comments (type, id, date_, memo_) VALUES (".$type.",".$max_type_no.",'".$sales_date."','".$memo."')";
        $result = $this->ddb->query($sql);
        if($result){
            return  true;
        }else{
            return false;
        }

     }

	public function get_next_trans_no_franchisee($aria_db = null){
        $this->ddb = $this->load->database($aria_db, true);
        $sql = "SELECT max(type_no) as max_type_no from 0_gl_trans WHERE type ='60'";
        $result = $this->ddb->query($sql);
        $result = $result->row();
        if($result){
            return  $result->max_type_no + 1;
        }else{
            return false;
        }

    }
	    public function get_ref_franchisee($aria_db = null){
        $this->ddb = $this->load->database($aria_db, true);
        $sql = "select  max(CAST(reference AS UNSIGNED)) as max_ref from 0_refs  where type ='60'";
        $result = $this->ddb->query($sql);
        $result = $result->row();
        if($result){
            return  $result->max_ref + 1;
        }else{
            return false;
        }

    }

	public function get_existed_sales_franchisee($sales_date = null,$aria_db = null){

        $this->ddb = $this->load->database($aria_db, true);
        $sql = "SELECT type_no FROM  0_gl_trans where tran_date = '".$sales_date."' and type ='60'";
		echo $sql;
        $result = $this->ddb->query($sql);
        $result = $result->row();
        if($result){
            return  $result->type_no;
        }else{
            return  false;
        }
    }

	public function delete_gl_franchisee($sales_date = null,$aria_db = null,$get_existed_sales = null){
        $this->ddb = $this->load->database($aria_db, true);
        $sql = "DELETE FROM 0_gl_trans where tran_date = '".$sales_date."' and type ='60' and type_no ='".$get_existed_sales."'";
        $result = $this->ddb->query($sql);
        if($result){
            return  true;
        }else{
            return false;
        }
    }


	public function get_sales_collection($sales_date = null,$aria_db = null,$type = null, $account = null){

        $this->ddb = $this->load->database($aria_db, true);
        $sql = "SELECT SUM(ABS(amount))+0 as gl_amount FROM  0_gl_trans where tran_date = '".$sales_date."' and type ='".$type."' and account IN (".$account.")";
        $result = $this->ddb->query($sql);
        $result = $result->row();
        if($result){
            return  $result->gl_amount;
        }else{
            return  0;
        }

    }


	public function insert_franchisee_header($data = array()){ 
		$this->db = $this->load->database("mysql_pos_db", true);
		$this->db->insert('order_header_franchisee', $data);
		//echo $this->db->last_query();
  		$insert_id = $this->db->insert_id();
		 return  $insert_id;
	 }

	 public function update_franchisee_details($old_ord,$order_id){
		$branch_name = BRANCH_NAME;
	   $this->db = $this->load->database("mysql_pos_db", true);
	   $sql = "UPDATE order_header_franchisee SET re_order_id=".$order_id."  WHERE order_id = ".$old_ord." ";
	  // echo $sql;
	   $res = $this->db->query($sql);
	   if($res){
		return $res;
	   }
	}

	public function update_comstore_srp($barcode,$srp,$uomqty,$uom){
	   $this->db = $this->load->database("main_branch_mysql", true);
	   $sql = "UPDATE pos_products 
	   SET srp = ".$srp.", 
	   LastDateModified = '".date('Y-m-d H:i:s')."',
	   uom = '".$uom."',
	   qty = ".$uomqty."
	   WHERE Barcode = '".$barcode."' 
	   and srp <= ".$srp."
	   ";
	   $res = $this->db->query($sql);
	   if($res){
		return $res;
	   }
	}

	public function update_srspos_comstore_srp($barcode,$srp,$uomqty,$uom){
		$this->db = $this->load->database("main_branch_mysql", true);
		$sql = "UPDATE 
		srspos.pos_products 
		SET srp = ".$srp.", 
		LastDateModified = '".date('Y-m-d H:i:s')."',
		uom = '".$uom."',
	    qty = ".$uomqty."		
		WHERE Barcode = '".$barcode."'
		and srp <= ".$srp."
		";
		$res = $this->db->query($sql);
		if($res){
		 return $res;
		}
	 }

	 public function update_comstore_srp_stat($barcodes){
		$branch_name = BRANCH_NAME;
		$this->db = $this->load->database("pricing_db_ms", true);
		$sql = "UPDATE comstore_srp_update SET throw = 1  where barcode in('".$barcodes."') and  br_code = '".$branch_name ."'";
		echo $sql;
	   $res = $this->db->query($sql);
	 }

	public function get_modified_srp(){
		
		$this->db = $this->load->database("pricing_db_ms", true);
		$branch_name = BRANCH_NAME;
		$sql = "select  TOP 500 * from comstore_srp_update where throw = 0 and  br_code = '".$branch_name ."'";
	   $res = $this->db->query($sql);
	   $res = $res->result_array();
	   return $res;
	}

	public function insert_new_modifed_srp(){
		
		$this->db = $this->load->database("pricing_db_ms", true);
		$branch_name = BRANCH_NAME;
		$past_3days = date("Y-m-d", strtotime("-3 days"));
		$sql = "INSERT into comstore_srp_update (barcode,pricemodecode,uom,srp,throw,br_code,uomqty)
		select Barcode,PriceModeCode,uom,srp,0,'".$branch_name."',qty from POS_Products where LastDateModified >='".$past_3days."' 
		and ProductID not in(select ProductID from products where LevelField1Code IN('10021','10056','10057','10058','10059','10060','10061','10062','10063','10064','10065','10066','9092'))
		and Barcode not in(select Barcode from comstore_srp_update where br_code = '".$branch_name ."')";

	   $res = $this->db->query($sql);
	   if($res){
		 return 'modified price has been inserted!';
	   }
	}


	public function update_modified_srp(){
		
		$this->db = $this->load->database("pricing_db_ms", true);
		$branch_name = BRANCH_NAME;
		$sql = "UPDATE a
		SET a.srp = b.srp,throw = 0
		from comstore_srp_update as a
		LEFT JOIN POS_Products as b
		on a.barcode = b.Barcode
		where a.srp < b.srp and a.br_code = '".$branch_name."' ";

	   $res = $this->db->query($sql);
	   if($res){
		 return 'modified price has been updated!';
	   }
	}

	 public function insert_franchisee_details($order_id,$old_ord){
		$branch_name = BRANCH_NAME;
	   $this->db = $this->load->database("mysql_pos_db", true);
	   $sql = "INSERT INTO order_details_franchisee (order_id,barcode,description,qty,srp,subtotal,category)
		select ".$order_id.",b.barcode,b.description,(b.qty - served_qty) as qty,b.srp,((b.qty - served_qty)*b.srp) as subtotal,b.category from order_header_franchisee as a LEFT JOIN 
		order_details_franchisee as b
		on a.order_id = b.order_id
		where a.TerminalNo is not null AND a.date_served >='2022-08-29' and category NOT IN('10055','10050','10053','10021','10056','10057','10058','10059','10060','10061','10062','10063','10064','10065','10066','9041')
		and re_order_id is null and a.order_id ='".$old_ord."'
		HAVING qty >= 1
		ORDER BY a.order_id
	   ";
	//   echo $sql;
	   $res = $this->db->query($sql);
	   if($res){
		return $res;
	   }
	}


	public function update_served_qty(){
		$branch_name = BRANCH_NAME;
	   $this->db = $this->load->database("mysql_pos_db", true);
	   $sql = "UPDATE 
	   order_details_franchisee  b RIGHT JOIN
	   `order_header_franchisee`   a  on a.order_id = b.order_id
	   LEFT JOIN (select Barcode,TransactionNo,TerminalNo,(sum(Qty) - sum(QtyReturned)) as served FROM tfinishedsales where Voided = 0 and cast(logdate as date) >='2022-08-23' GROUP BY Barcode,TransactionNo,TerminalNo )  as  tf 
	   ON a.TerminalNo = tf.TerminalNo and a.TransactionNo = tf.TransactionNo and b.barcode = tf.Barcode 
	   SET b.served_qty = CASE WHEN tf.Barcode is null THEN 0 ELSE tf.served  END
	   where a.TerminalNo is not null AND a.date_served >='2022-08-29' and (b.served_qty is  null  or b.served_qty =0) and re_order_id is null 
	   ";
	   //echo $sql;
	   $res = $this->db->query($sql);
	   if($res){
		return $res;
	   }
	  
	}	

	public function get_re_order(){
	 $branch_name = BRANCH_NAME;
	   $this->db = $this->load->database("mysql_pos_db", true);
	   $sql = "select  a.order_id as old_ord ,customer_name,
				sum(b.qty - b.served_qty) as unserved , 
				SUM(((b.qty - b.served_qty)*b.srp)) as total from order_header_franchisee as a LEFT JOIN 
				order_details_franchisee as b
				on a.order_id = b.order_id
				where a.TerminalNo is not null AND a.date_served >='2022-08-29'
				and category NOT IN('10055','10050','10053','10021','10056','10057','10058','10059','10060','10061','10062','10063','10064','10065','10066','9041')
				and re_order_id is null  
				GROUP BY a.order_id,customer_name
				HAVING unserved >= 1
				ORDER BY a.order_id";
	   //echo $sql;
	   $res = $this->db->query($sql);
	   $res = $res->result_array();
	   return $res;
	   
	}

	public function get_re_order_id(){
		$branch_name = BRANCH_NAME;
		  $this->db = $this->load->database("mysql_pos_db", true);
		  $sql = "select max(id)+1 as id from order_header_franchisee ";
		  $res = $this->db->query($sql);
		  $res = $res->row();
		  return $res->id;
		  
	   }

	 public function get_new_gulay_price(){
	   $this->db = $this->load->database("main_nova", true);
		$sql = "select ProductID,barcode,markup,srp,LastDateModified from POS_Products where ProductID
				in(
				select ProductID from Products where LevelField1Code ='9092' and LevelField2Code ='0006'
				) and cast(LastDateModified as date) >='".date('Y-m-d',strtotime('-2 days'))."'";
	   // echo  $sql;
		$res = $this->db->query($sql);
	    $res = $res->result_array();
	    return $res;
	 }
	 
	 public function insert_header($data = array()){ 
		$this->db = $this->load->database("mysql_fran_db", true);
		$this->db->insert('0_transfer_header', $data);
  		 $insert_id = $this->db->insert_id();
		 return  $insert_id;
	 }

	 function insert_batch_details($data=array()){
		$this->db = $this->load->database("mysql_fran_db", True);
		$this->db->insert_batch("0_transfer_details", $data);
	}
	 

	 public function get_res_for_trans($TransactionNo,$TerminalNo){ 
		//$this->db = $this->load->database("mysql_pos_db_franchisee", true);
		$this->db = $this->load->database("mysql_pos_db", true);
		$sql = "select ProductID as stock_id,ProductID as stock_id_2,Description as description,
		Barcode as barcode,UOM as uom,IFNULL(Price,0) as cost ,
		IFNULL(price,0) as net_of_vat ,sum(Qty) as qty_out, sum(Qty) as actual_qty_out  
		from tfinishedsales where TransactionNo='".$TransactionNo."' and TerminalNo ='".$TerminalNo."'
		and Voided = 0 and `Return` = 0
		GROUP BY productID,ProductID,Description,Barcode,UOM,Price,price
		ORDER BY UOM";
		$res = $this->db->query($sql);
	    $res = $res->result_array();
	    return $res;
	 }
	 public function get_unprocessed_transfer_fr(){
		 
		 $branch_name = BRANCH_NAME;
		 $this->db = $this->load->database("mysql_pos_db", true);
		
		$sql = "select id,TransactionNo,TerminalNo,date_served from order_header_franchisee 
				WHERE transferstatus ='0' and TransactionNo is not null and customer_name like '%$branch_name%' ";
				
		if($branch_name == 'BATASAN'){
			$sql = "select customer_name,id,TransactionNo,TerminalNo,date_served from order_header_franchisee 
			WHERE transferstatus ='0' and TransactionNo is not null and 
			(customer_name = 'BATASAN' or  customer_name = 'BATASAN_MANUAL')";
		}
		
		//echo $sql;
		$res = $this->db->query($sql);
	    $res = $res->result_array();
	    return $res;
		
	 }

	 public function update_pos_order_stat($id){
		$this->db = $this->load->database("mysql_pos_db", true);
		$sql = "UPDATE order_header_franchisee SET transferstatus = 1 where id =".$id." ";
		$res = $this->db->query($sql);
		
	 }


	  public function upd_gulay_price($rows, $date_){
    	$this->db= $this->load->database("branch_nova", true);
    	foreach($rows as $row)
	     {
			echo $row["ProductID"].'updated'.PHP_EOL;
			
			$sql = "UPDATE POS_Products SET 
					markup = ". $row['markup'] .",
					srp = ". $row['srp'] .",
					LastDateModified = '". date('Y-m-d',strtotime($row['LastDateModified']))."'
				WHERE ProductID = '". $row["ProductID"]."' and  barcode = '". $row["barcode"]."' ";
				
			$this->db->query($sql);
	     }
    }

    public function get_customer_code(){
        $array = array();
    	$this->db = $this->load->database("default", true); 
        $result = $this->db->get("excluded_whoiesale_customer");
        $result = $result->result_array();
	    foreach($result as $row) array_push($array,  $row["wholesale_customer_name"]);	
    	return $array;
    } 

    public function delete_product_history($date){
    	  $this->db = $this->load->database("default", true); 
          $this->db->where("date_posted", $date);
          $this->db->delete("product_history");    
    } 

    public function get_default_vendor(){
    	$this->db = $this->load->database("default", TRUE);
		$this->db->select("distinct(vendor) as vendorcode, user_id");
		$query = $this->db->get("user_vendor");
		return $query->result_array();
    }

    public function insert_out_of_stock($data){
        $this->db= $this->load->database("default", true);
        if(count($data) == 1)  $this->db->insert("out_of_stock", $data[0]);
        else if(count($data) > 1) $this->db->insert_batch("out_of_stock", $data);
    }

    public function get_out_of_stock_po_date($product_id,$br_code){
		$this->db = $this->load->database('po', TRUE);
		$this->db->select('purch_orders.delivery_date as delivery_date, purch_order_details.ord_qty');
		$this->db->from('purch_orders');
		$this->db->join('purch_order_details', 'purch_order_details.order_no = purch_orders.order_no');
		$this->db->where('purch_order_details.stock_id', $product_id);
		$this->db->where('purch_orders.trans_type', 16);
		$this->db->where('purch_order_details.trans_type', 16);
		$this->db->where('purch_orders.status =', 0);
		$this->db->where('purch_orders.br_code', $br_code);
		$this->db->where('purch_orders.manual', 0);
		$this->db->order_by('purch_orders.delivery_date desc');
		$this->db->limit(1);
		$result =  $this->db->get();
		$query = $result->result(); 
		if($query)
		return $query[0];
		else return array();
	}
 
    public function get_user_vendor($user_id=null, $user_vendor = null, $not_in = array())
	{
		$this->db = $this->load->database('default', TRUE);
		$this->db->select('user_vendor.*');
		$this->db->from('user_vendor');


		if($user_id!=null) $this->db->where('user_vendor.user_id =',$user_id);
                if($user_vendor != null) $this->db->where('user_vendor.vendor' , $user_vendor);
                if(!empty($not_in)) $this->db->where_not_in("user_vendor.vendor", $not_in);
		$this->db->group_by('user_vendor.vendor');

		$query =  $this->db->get();

		$row = $query->result();
		return $row;
	}



	public function get_vendor($code){
		$this->db = $this->load->database($this->local_db, TRUE);
		$this->db->select('*');
		$this->db->from('vendor');
		$this->db->where('vendor.vendorcode',$code);
		$result =  $this->db->get();
		return $result->result(); 
	}

 public function auto_get_srs_suppliers_item_details2nd($item_code=null,$sup_code=null){
		$this->db = $this->load->database($this->local_db, TRUE);
		$exclude_db = $this->load->database("default", true);
		$exclude_db->where("VendorCode", $sup_code);
		$exclude_db->select("ProductID");
		$exclude_items = $exclude_db->get("exclude_items");
		$exclude_items = $exclude_items->result_array();
		$items = array();
		$excludes_category = array('10055','10050','10053','10021','10056','10057','10058','10059','10060','10061','10062','10063','10064','10065','10066','9041');
		foreach($exclude_items as $index => $it) array_push($items, $it["ProductID"]);
		
		$this->db->select('vendor_products.VendorProductCode,
					  vendor_products.Description,
					  vendor_products.ProductID,
					  products.ProductCode,
					  vendor_products.VendorCode,
					  CASE WHEN products.LevelField1Code = 9019 THEN 0.995 ELSE 0.98 END as srs_percentage, 
					  vendor.description as vendor_description,
					  vendor_products.uom,
					  vendor_products.cost,
					  vendor_products.discountcode1,
					  vendor_products.discountcode2,
					  vendor_products.discountcode3,
					  ((products.StockRoom + products.SellingArea) / vendor_products.qty) as StockRoom,
					  vendor_products.qty as reportqty,
					  pos_products.srp as srp,
					  products.LevelField1Code,					  
					 ');
		$this->db->from('vendor_products');
		$this->db->join('products','vendor_products.ProductID = products.ProductID');
		$this->db->join('vendor','vendor.vendorcode = vendor_products.VendorCode');
		$this->db->join('pos_products','vendor_products.ProductID = pos_products.ProductID AND vendor_products.uom = pos_products.uom');
        if(!empty($items))  $this->db->where_not_in("vendor_products.ProductID", $items);
		$this->db->where_not_in("products.LevelField1", $excludes_category);
		$this->db->where('products.inactive =','0');
                $this->db->where('vendor_products.defa =', 1);
		$this->db->where_in('vendor_products.ProductID',$item_code);
	        if($sup_code != null) $this->db->where('vendor.vendorcode =',$sup_code);	
		$this->db->order_by('vendor_products.Description');
		$query =  $this->db->get();
		return $query->result();
	}

function overstock_offtake($from,$to,$items=array(), $days = 30){
	
		$this->db = $this->load->database("default", TRUE);
		$this->db->select("product_history.product_id,'".$days."' as divisor,sum(product_history.selling_area_out) as total_sales",false);
		$this->db->from('product_history');
			
		$this->db->where('product_history.date_posted >=',$from);	
		$this->db->where('product_history.date_posted <=',$to);	
		// $this->db->where('product_history.selling_area_out >','0');	
		//$this->db->where('((product_history.day_total > 0) OR  (selling_area_out > 0))');	
		if(!empty($items))
			$this->db->where_in('product_history.product_id',$items);
		$this->db->group_by('product_history.product_id');
		$query =  $this->db->get();

		return $query->result();
	}

	public function get_srs_suppliers_item_details($item_code=null,$sup_code=null){
		$this->db = $this->load->database($this->main_db, TRUE);
		$sql = "select 
		fs.Description as Description, 
		fs.ProductID as ProductID, 
		fs.Barcode as ProductCode, 
		'SANROB001' as VendorCode,
		fs.UOM as uom, 
		fs.AverageUnitCost as cost, 
		'' as discountcode1,
		'' as  discountcode2,
		'' as discountcode3,
		pp.sellingarea as StockRoom,
		fs.Packing as reportqty,
		pp.srs_percentage,
		pp.LevelField1Code
		from 
		(select  ProductID,Barcode,Description,uom,Packing,AverageUnitCost,Price From tfinishedsales
		WHERE cast(LogDate as date) >=DATE_SUB(now(), INTERVAL 1 MONTH) and  cast(LogDate as date) <=now() 
		AND (ProductID, Packing) IN(
		select  ProductID,MIN(Packing)From tfinishedsales
		WHERE cast(LogDate as date) >=DATE_SUB(now(), INTERVAL 1 MONTH) and  cast(LogDate as date) <=now() 
		GROUP BY ProductID)
		GROUP BY ProductID ORDER BY productid) as fs
		LEFT JOIN (select  productid,sellingarea,CASE WHEN products.LevelField1Code = 9019 THEN 0.9975 ELSE 0.975 END as srs_percentage, LevelField1Code,inactive from  products ) as pp
		on fs.ProductID = pp.productid
		where pp.LevelField1Code  not in ('10055','10050','10053','10055','10050','10053','10021','10056','10057','10058','10059','10060','10061','10062','10063','10064','10065','10066','9041') and pp.inactive = 0
		ORDER BY Description";
		

		$res = $this->db->query($sql);
		$res = $res->result();
		return $res;

	}

 /* public function get_srs_suppliers_item_details($item_code=null,$sup_code=null){
		$this->db = $this->load->database($this->local_db, TRUE);
		$exclude_db = $this->load->database("default", true);
	//	$exclude_db->where("VendorCode", $sup_code);
		$excludes_category = array('10055','10050','10053','10021','10056','10057','10058','10059','10060','10061','10062','10063','10064','10065','10066');
		$exclude_db->select("ProductID");
		$exclude_items = $exclude_db->get("exclude_items");
		$exclude_items = $exclude_items->result_array();
		$items = array();
		foreach($exclude_items as $index => $it) array_push($items, $it["ProductID"]);
		$this->db->select('vendor_products.VendorProductCode,
					  vendor_products.Description,
					  vendor_products.ProductID,
					  products.ProductCode,
					  "SANROB001" as VendorCode,
					  vendor_products.uom,
					  vendor_products.cost,
					  vendor_products.discountcode1,
					  vendor_products.discountcode2,
					  vendor_products.discountcode3,
					  ((products.StockRoom + products.SellingArea) / vendor_products.qty) as StockRoom,
					  vendor_products.qty as reportqty					  
					 ');
		$this->db->from('vendor_products');
		$this->db->join('products','vendor_products.ProductID = products.ProductID');
		  $this->db->where('products.inactive',0);
		 // $this->db->where('products.sellingarea != ',0);
		$this->db->where('vendor_products.defa',1);
		$this->db->where_not_in("products.LevelField1Code", $excludes_category);
		if(!empty($items))  $this->db->where_not_in("vendor_products.ProductID", $items);
		if($item_code != null){
			$this->db->where('vendor_products.ProductID =',$item_code);
		}
		if($sup_code != null)
		//	$this->db->where('vendor_products.VendorCode =',$sup_code);
		//$this->db->limit(1);
		$this->db->order_by('vendor_products.Description');
		$query =  $this->db->get();
		 // echo $this->db->last_query();
		return $query->result();
	}
*/

	public function get_vendor_products($code){
		$this->db = $this->load->database($this->local_db, TRUE);
		$this->db->select('ProductID, Description');
		$this->db->from('vendor_products');
		$this->db->where('vendor_products.VendorCode',$code);
		$result =  $this->db->get();
        return $result->result(); 
	}

	function get_item_total_sales($date)
    {
      $this->db = $this->load->database($this->local_db, TRUE);
	  $sql = "SELECT ProductID,
					SUM(Extended) AS total_sales,
					SUM(AverageUnitCost * 
						(CASE
							WHEN [Return] = 0 THEN
								TotalQty
							ELSE
								- TotalQty
							END
						)
						) AS total_cost,
					SUM((CASE
							WHEN [Return] = 0 THEN
								TotalQty
							ELSE
								- TotalQty
							END
						))AS qty
				FROM
					[dbo].[FinishedSales]
				WHERE CAST(LogDate as DATE)= '".$date."'
				AND Voided = 0
				GROUP BY ProductID, LogDate";
					
	
	    $res = $this->db->query($sql);
	    $res = $res->result_array();
	    return $res;
    }

    function insert_prod_history_summary_sales($rows,$date_posted)
    {
    	$this->db = $this->load->database("default", true);
    	foreach($rows as $row){
		$prod_id = $row["ProductID"] + 0;
		$t_sales = $row["total_sales"] + 0;
		$t_cost = $row["total_cost"] + 0;
		$t_sales_qty = $row["qty"] + 0;
		$w_sales = 0;

		$sql = " INSERT INTO product_history (date_posted, product_id, beginning_selling_area, selling_area_in, selling_area_out, day_total,
					wholesale_qty,total_sales,total_cost)
			VALUES('".$date_posted."',".$prod_id.",0,0,$t_sales_qty,0,$w_sales, $t_sales, $t_cost) ";
			$this->db->query($sql);
	    }
    }

    function update_excluded_wholesale($date_)
    {
	    $supp_codes = $this->customer_code;
	    if(empty($supp_codes)) return array();
	    $where = (count($supp_codes) > 1) ? "b.CustomerCode IN ('". implode("','",$supp_codes) ."')" : "b.CustomerCode = '".$supp_codes[0]."'";
		//$where = (count($supp_codes) > 1) ? "a.CustomerCode IN ('". implode("','",$supp_codes) ."')" : "a.CustomerCode = '".$supp_codes[0]."'";
	    $this->db= $this->load->database($this->local_db, TRUE);
	   
	   // $sql = "SELECT b.ProductID, b.Description, CONVERT(DECIMAL(16,2), SUM(b.TotalQty)) as ws_total
					// FROM FinishedTransaction a
					// JOIN FinishedSales b ON (a.TransactionNo = b.TransactionNo AND a.TerminalNo = b.TerminalNo)
					// WHERE ".$where." AND
					// CAST(a.LogDate as DATE) = '".$date_."'
					// GROUP BY b.ProductID, b.Description
					// ORDER BY b.Description";
					
		$sql = "SELECT a.ProductID,
		a.Description,
		SUM((CASE WHEN a.[Return] = 0 THEN a.TotalQty ELSE -a.TotalQty END)) as ws_total
		FROM [FinishedSales] as a
		LEFT JOIN FinishedTransaction b ON (a.TransactionNo = b.TransactionNo AND a.TerminalNo = b.TerminalNo)
		WHERE ".$where."
		AND CAST(a.LogDate as DATE)= '".$date_."'
		AND CAST(b.LogDate as DATE)= '".$date_."'
		AND a.Voided = 0
		GROUP BY a.ProductID, a.Description
		ORDER BY a.Description";

		$res = $this->db->query($sql);
	    $res = $res->result();
	    return $res;
    }

    function update_wholesale($rows, $date_){
    	$this->db= $this->load->database("default", true);
    	foreach($rows as $row)
	     {
			$sql = "UPDATE product_history SET 
								wholesale_qty = ". $row['ws_total'] ."
							WHERE date_posted = '".$date_."'
							AND product_id = ". $row["ProductID"];
			$this->db->query($sql);
	     }
    }

	public function get_custom_val($tbl,$col,$where,$val){
		$this->db = $this->load->database($this->local_db, TRUE);
		if(is_array($col)){
			$colTxt = "";
			foreach ($col as $col_txt) {
				$colTxt .= $col_txt.",";
			}
			$colTxt = substr($colTxt,0,-1);
			$this->db->select($tbl.".".$colTxt);
		}
		else{
			$this->db->select($tbl.".".$col);
		}

		$this->db->from($tbl);
		$this->db->where($tbl.".".$where,$val);
		$query = $this->db->get();
		$result = $query->result();
		if(count($result) > 0){
			return $result[0];
		}
		else
			return "";
	}

	public function execute_queue($query = array(), $id, $table = "auto_purchase"){
            $this->db = $this->load->database("main_po", TRUE);
            if($this->db->conn_id){
                if($this->db->insert($table,$query)){
                    $this->db = $this->load->database("default", true);
                    $this->db->where("id", $id);
                    $this->db->update($table, array("throw"=>1));
                    return true;       
                } else return false;
            } return false;
    } 
   

    public function throw_po(){
    	$this->db =$this->load->database("default", true);
    	$this->db->where("throw", 0);
    	$query = $this->db->get("auto_purchase");	
    	return $query->result_array();
    }

    public function throw_os(){
    	$this->db =$this->load->database("default", true);
    	$this->db->where("throw", 0);
    	$query = $this->db->get("out_of_stock");	
    	return $query->result_array();
    }

    

    public function get_srs_suppliers_details($code=null){
        $this->db = $this->load->database($this->local_db, TRUE);
        $this->db->select('vendor.vendorcode,
                      vendor.description,
                      vendor.address,
                      vendor.city,
                      vendor.zipcode,
                      vendor.contactperson,
                      vendor.country,
                      vendor.email,
                      terms.description as term_desc
                     ');
        $this->db->from('vendor');
        $this->db->join('terms','vendor.terms = terms.TermID','left');
        if($code != null)
            $this->db->where('vendor.vendorcode =',$code);
        $query =  $this->db->get();
        return $query->result();
    }

   

	function auto_save_details($head,$items,$user_id, $auto_po = 0, $branch, $supplier){
			$this->db = $this->load->database("default", True);
			$this->db->insert("auto_purchase", array("date_added"=>date("Y-m-d"),"branch"=>$branch, "supplier"=>$supplier, "user_id" => $user_id, "po_head" => $head, "po_details" =>$items, "auto_po" => $auto_po ) );
	}

	public function get_franchise_cost($items=array()){
		
        $this->db = $this->load->database($this->pricing_db, TRUE);
		$sql ="select pp.Barcode as Barcode,vp.ProductID as ProductID,vp.averagenetcost as CostOfSales,vp.VendorCode as VendorCode From POS_Products as pp
		LEFT JOIN VENDOR_Products as vp on pp.ProductID = vp.ProductID and defa = 1
		where pp.barcode in(".$items.")";
		/* $sql ="SELECT p.ProductID,
		CASE WHEN p.CostOfSales = 0 THEN vp.Averagenetcost ELSE p.CostOfSales END as CostOfSales
		 FROM [dbo].[Products] as p
		left JOIN VENDOR_Products as vp
		on p.ProductID = vp.ProductID and defa = 1
		where p.ProductID in (".$items.") "; */
		$res = $this->db->query($sql);
		$res = $res->result_array();
      return $res;
    }

	
	public function get_max_fr_id(){
		
        $this->db = $this->load->database('mysql_pos_db', TRUE);
		$sql ="SELECT (CASE WHEN isnull(max(order_id)) THEN 0 ELSE max(order_id) END)+1 as order_id FROM `order_header_franchisee`;";
		$res = $this->db->query($sql);
		$res = $res->row();
      return $res->order_id;
    }

	function insert_batch($data=array()){
		$this->db = $this->load->database("mysql_pos_db", True);
		$this->db->insert_batch("order_details_franchisee", $data);
	}

	function insert($data=array()){
		$this->db = $this->load->database("mysql_pos_db", True);
		$this->db->insert("order_header_franchisee", $data);
	}

	function must_have_seven_days_sale($date = null){
		$this->db = $this->load->database("default", true);
		$dates = date('Y-m-d');
		if($date){
			$dates = $date;
		}
		$past_seven_days =  date('Y-m-d', strtotime('-30 days',strtotime($dates)));
		 $sql = "select count(a.date_posted) as number_of_sales from(
					SELECT date_posted FROM `product_history` where date_posted >='".$past_seven_days."'
					 GROUP BY date_posted) as a";
		
		$res = $this->db->query($sql);
	    $res = $res->row();
	    return $res->number_of_sales;


	}

	function get_srs_items_po_divisor($from,$to,$items=array(), $divisor_ ){


		$this->db = $this->load->database("default", true);
		$this->db->select("product_history.product_id,".$divisor_." as divisor,sum(product_history.selling_area_out) - sum(product_history.wholesale_qty) as total_sales",false);
		$this->db->from('product_history');
			
		$this->db->where('product_history.date_posted >=',$from);	
		$this->db->where('product_history.date_posted <=',$to);	
		// $this->db->where('product_history.selling_area_out >','0');	
		$this->db->where('((product_history.day_total > 0) OR  (selling_area_out > 0))');	
		if(!empty($items))
			$this->db->where_in('product_history.product_id',$items);
		$this->db->group_by('product_history.product_id');
		$query =  $this->db->get();
		$d =  $this->db->last_query();
		//echo $d;
		return $query->result();
	

	}

	public function get_srs_discounts($discs=array()){
		$this->db = $this->load->database($this->local_db, TRUE);
		$this->db->select('Discounts.*');
		$this->db->from('Discounts');
		$this->db->where_in('Discounts.DiscountCode',$discs);	
		$query =  $this->db->get();
		return $query->result();
	}

	public function get_selling_days_item_by_supplier_branch($branch_code,$supplier_code,$product_code){
        $this->db = $this->load->database('default', TRUE);
		$this->db->select(' ifnull(supplier_frequency_items.selling_days, 0) as selling_days ',false);
		$this->db->from('supplier_frequency_items');
		$this->db->join('supplier_frequency', 'supplier_frequency.id = supplier_frequency_items.supplier_frequency_id');
		$this->db->where('supplier_frequency.supplier =',$supplier_code);
		$this->db->where('supplier_frequency_items.product_code =',$product_code);
		$this->db->where('supplier_frequency.branch =',$branch_code);
		$this->db->limit(1);
		$query =  $this->db->get();
		$row = $query->result(); 
		if(count($row) == 0) return 0;
		return $row[0]->selling_days;
	}

	public function main_get_branch_details($branch_code){	
		$this->db = $this->load->database('default', TRUE);
		$this->db->select('*');
		$this->db->from('branches');
		$this->db->where('code ',$branch_code);
		$query =  $this->db->get();
		$row = $query->result();
		return $row[0];
	}

	 public function getAllTruckLoad($id){
        $this->db = $this->load->database("default", true);
        $this->db->where("supplier_id", $id);
        $result = $this->db->get("truck_load");
        $result = $result->result_array();
        $array = array();
        foreach($result as $row) $array[$row["uom"]] = $row["qty"];
        return $array;
    }
	
	public function get_frequency_excluded(){
		$this->db = $this->load->database('default', TRUE);
		$sql = "SELECT vendor_code FROM supplier_frequency_excluded";
		$query = $this->db->query($sql);
	    $result = $query->result();
		
		foreach ($result as $vendor_res) {
			$excluded_vendors[] = "".$vendor_res->vendor_code."";
		}

	    return $excluded_vendors;
	}

    public function get_frequency($user_id=null,$branch = null, $supplier = null,$limit=null,$status=null,$resume=null,$frequency=null,$week_day=null, $auto_po = null,$excluded_vendors){
        $this->db = $this->load->database('default', TRUE);
        $this->db->select('supplier_frequency.date_created,
							supplier_frequency.id,
							branches.ci_ms_database as ms,
							supplier_frequency.valid_until,
							supplier_frequency.delivery_date,
							supplier_frequency.selling,
							supplier_frequency.supplier,
							branches.name,
							supplier_frequency.frequency,
							supplier_frequency.days,
							branches.code,
							supplier_frequency.branch as sf_branch,
							"SANROB001" as sf_supplier,
							supplier_frequency.valid_until as valid_until,
							supplier_frequency.delivery_date as delivery_day,
							supplier_frequency.auto_po as auto_po,
							supplier_frequency.status,
							users.fname,
							users.lname,
							supplier_frequency.resume,
							supplier_frequency.user_id as user_id,
							supplier_frequency.within_period as within_period,
							supplier_frequency.minimum,
							supplier_frequency.maximum,
							supplier_frequency.min_pc');
        $this->db->from('supplier_frequency');
        $this->db->join('branches','supplier_frequency.branch = branches.code');
        //$this->db->join('user_vendor','user_vendor.vendor = supplier_frequency.supplier');
                $this->db->join('users','users.id = supplier_frequency.user_id');
        if($branch != null)
            $this->db->where('supplier_frequency.branch', $branch);
        if($supplier != null)
            $this->db->where('supplier_frequency.supplier', $supplier);
        if($user_id != null)
            $this->db->where('users.id', $user_id);

        if($status != null) 
            $this->db->where('supplier_frequency.status',$status);
        //if($auto_po != null) $this->db->where("auto_po", $auto_po);
                if($resume != null)
                        $this->db->where('supplier_frequency.resume',$resume);

        if($limit !=null)
            $this->db->limit($limit[0],$limit[1]);

                if($frequency != null)
                        $this->db->where('supplier_frequency.frequency',$frequency);

                 if($week_day != null)
                        $this->db->where('supplier_frequency.days',$week_day);
					
					$this->db->where_not_in('supplier_frequency.supplier', $excluded_vendors);

                $this->db->order_by('branches.name');
                 $this->db->order_by('supplier_frequency.supplier');
        $query = $this->db->get();
                //echo $this->db->last_query();
        return $query->result_array();
    }

	
}