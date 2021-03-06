<?php
defined('BASEPATH') OR exit('No direct script access allowed');
ini_set('MAX_EXECUTION_TIME', -1);
ini_set('mssql.connect_timeout',0);
ini_set('mssql.timeout',0);
set_time_limit(0);  
ini_set('memory_limit', -1);

class Fresh_auto_model extends CI_Model {
	
	public $local_db, $customer_code = array();
		public function __construct(){
		parent::__construct();
		$this->local_db = "branch_nova";

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
	
	public function get_frequency_fresh($user_id=null,$branch = null, $supplier = null,$limit=null,$status=null,$resume=null,$frequency=null,$week_day=null, $auto_po = null,$excluded_vendors){
        $this->db = $this->load->database('default', TRUE);
        $this->db->select('supplier_frequency.date_created,supplier_frequency.id,
        	branches.ci_ms_database as ms,supplier_frequency.valid_until, supplier_frequency.delivery_date, 
        	supplier_frequency.selling, supplier_frequency.supplier, branches.name, supplier_frequency.frequency,
        	 supplier_frequency.days, branches.code,  supplier_frequency.branch as sf_branch, 
        	 supplier_frequency.supplier as sf_supplier, supplier_frequency.valid_until as valid_until,
        	  supplier_frequency.delivery_date as delivery_day,supplier_frequency.auto_po as auto_po,supplier_frequency.status, 
        	  users.fname, users.lname,supplier_frequency.resume,supplier_frequency.user_id as user_id,supplier_frequency.within_period as within_period,supplier_frequency.minimum,supplier_frequency.maximum, supplier_frequency.min_pc');
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
					
						$this->db->where_in('supplier_frequency.supplier', $excluded_vendors);

                $this->db->order_by('branches.name');
                 $this->db->order_by('supplier_frequency.supplier');
        $query = $this->db->get();
              //  echo $this->db->last_query();
        return $query->result_array();
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

	public function get_srs_suppliers_item_details($item_code=null,$sup_code=null){
		$this->db = $this->load->database($this->local_db, TRUE);
		$exclude_db = $this->load->database("default", true);
		$exclude_db->where("VendorCode", $sup_code);
		$exclude_db->select("ProductID");
		$exclude_items = $exclude_db->get("exclude_items");
		$exclude_items = $exclude_items->result_array();
		$items = array();
		foreach($exclude_items as $index => $it) array_push($items, $it["ProductID"]);
		$this->db->select('vendor_products.VendorProductCode,
					  vendor_products.Description,
					  vendor_products.ProductID,
					  products.ProductCode,
					  vendor_products.VendorCode,
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
		$this->db->where('vendor_products.defa',1);
		if(!empty($items))  $this->db->where_not_in("vendor_products.ProductID", $items);
		if($item_code != null)                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                     
		{
			$this->db->where('vendor_products.ProductID =',$item_code);
		}
		if($sup_code != null)
			$this->db->where('vendor_products.VendorCode =',$sup_code);
		$this->db->order_by('vendor_products.Description');
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
	
	function get_srs_items_po_divisor($from,$to,$items=array()){
		$this->db = $this->load->database("default", true);
		$this->db->select("product_history.product_id,'30' as divisor,sum(product_history.selling_area_out) - sum(product_history.wholesale_qty) as total_sales",false);
		$this->db->from('product_history');
			
		$this->db->where('product_history.date_posted >=',$from);	
		$this->db->where('product_history.date_posted <=',$to);	
		// $this->db->where('product_history.selling_area_out >','0');	
		$this->db->where('((product_history.day_total > 0) OR  (selling_area_out > 0))');	
		if(!empty($items))
			$this->db->where_in('product_history.product_id',$items);
		$this->db->group_by('product_history.product_id');
		$query =  $this->db->get();
		return $query->result();
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
	
	public function get_srs_discounts($discs=array()){
		$this->db = $this->load->database($this->local_db, TRUE);
		$this->db->select('Discounts.*');
		$this->db->from('Discounts');
		$this->db->where_in('Discounts.DiscountCode',$discs);	
		$query =  $this->db->get();
		return $query->result();
	}
	
	function auto_save_details($head,$items,$user_id, $auto_po = 0, $branch, $supplier){
			$this->db = $this->load->database("default", True);
			$this->db->insert("auto_purchase", array("date_added"=>date("Y-m-d"),"branch"=>$branch, "supplier"=>$supplier, "user_id" => $user_id, "po_head" => $head, "po_details" =>$items, "auto_po" => $auto_po ) );
	}
	
	public function throw_po(){
    	$this->db =$this->load->database("default", true);
    	$this->db->where("throw", 0);
    	$query = $this->db->get("auto_purchase");	
    	return $query->result_array();
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
	
}