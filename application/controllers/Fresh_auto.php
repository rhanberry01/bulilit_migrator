<?php
if ( ! defined('BASEPATH')) exit('No direct script access allowed');
ini_set('MAX_EXECUTION_TIME', -1);
ini_set('mssql.connect_timeout',0);
ini_set('mssql.timeout',0);
set_time_limit(0);  
ini_set('memory_limit', -1);

//client_buffer_max_kb_size = '50240'
//sqlsrv.ClientBufferMaxKBSize = 50240

class Fresh_auto extends CI_Controller {
	
	public function __construct(){
		parent::__construct();
		date_default_timezone_set('Asia/Manila');
		$real_date = date("Y-m-d");
		$to = date("Y-m-d",strtotime($real_date .' -1 days'));
		$from = date("Y-m-d",strtotime($to .' -29 days'));
		define("FROM", $from);
		define("TO", $to);
		
		$this->load->model("Fresh_auto_model", "auto");
    }
	
	 public function index_fresh(){
       $user = null;
         echo date("Y-m-d h:i:s").PHP_EOL;
         $supplier = null;
         //$supplier = "COINFD001";
		$excluded_vendors=array();
		$excluded_vendors = $this->auto->get_frequency_excluded();
		//$excluded_vendors=implode(",", $excluded_vendors);
		$supplier_frequency = $this->auto->get_frequency_fresh($user,BRANCH_USE,$supplier,array(),"1","1", null, null, "1",$excluded_vendors);
       /* echo var_dump($supplier_frequency);
        die();*/
        $last_date = date('Y-m-t');
        $last_day = explode("-", $last_date);
        $day_last = $last_day[2];
        $month = date('F');
        $year = date('Y');
        $dayToday = date('l',strtotime(date('Y-m-d')));
        $date_today = date('Y-m-d');
        $special_array = array("F1", "F5", "F6", "F7");
        $to = TO;
        $from = FROM;
         foreach($supplier_frequency as $sf){
            $date_created = $sf["date_created"];
            unset($sf["date_created"]);
            $day_chosen = $sf["days"];
            $po_schedule = $sf["frequency"];
            
            //if($po_schedule == "F4") continue;

			// if($po_schedule == 'F4' && $day_chosen == $dayToday){
			// $sf["from"] = $from;
			// $sf["to"] = $to;
			// $this->auto_generate_pr($sf);   
			// }
            if($po_schedule == 'F4' && ($dayToday!='Sunday' && $dayToday!='Saturday')){
                $sf["from"] = $from;
                $sf["to"] = $to;
                $this->auto_generate_pr($sf);   
            }
            // else if( in_array($po_schedule,$special_array) && $day_chosen == $dayToday)
            // {
                // $check_week = 0;

                // for($x=1; $x< $day_last; $x++){
                    
                    // if(strlen($x) != 2)
                        // $append = '0'.$x;
                    // else
                        // $append = $x;

                    // $day_pick = date('Y-m-'.$append);
                    
                    // $dayCheck = date('l',strtotime($day_pick));

                    // if($dayCheck == $day_chosen){

                        // $check_week++;
                        // if($po_schedule == "F1" and $check_week == 1){
                             // $first_date = $day_pick;
                            // break;
                        // }
                        // else if($po_schedule == "F5" and $check_week == 2){
                             // $first_date = $day_pick;
                            // break;
                        // }
                        // else if($po_schedule == "F6" and $check_week == 3){
                             // $first_date = $day_pick;
                            // break;
                        // }
                        // else if($po_schedule == "F7" and $check_week == 4){
                             // $first_date = $day_pick;
                            // break;
                        // }
                    // }
                // }
               
                // if($first_date == $date_today)
                // {
                    // $sf["from"] = $from;
                    // $sf["to"] = $to;
                    // $this->auto_generate_pr($sf);
                // }
            // }
            // else if($po_schedule == 'F2'){
                // $po_to = date("Y-m-d", strtotime($date_today));
                // $po_from = date("Y-m-d", strtotime($date_created));
                // if($po_to > $po_from){
                    // $detector = $this->date_diff2($po_to, $po_from, "d");
                    // if($detector != 0){
                        // $determine = (int) ($detector / 7);
                        // if( $determine % 2 == 0 && $day_chosen == $dayToday){
                            // $sf["from"] = $from;
                            // $sf["to"] = $to;
                            // $this->auto_generate_pr($sf); 
                        // }
                    // }
                // }
            // } 
        }
        $this->throw_po();
        echo date("Y-m-d h:i:s").PHP_EOL;
    }
	
	public function auto_generate_pr($sf){
		
		echo $sf["frequency"].PHP_EOL;
    	$this->po_set_settings($sf);
		//echo '1';
        echo "Set Settings" . PHP_EOL;  
        $this->create_purchase_request();
		//echo '2';
        echo "Set Form" . PHP_EOL;  
        $this->save_po($sf);
		//echo '3';
        echo "Set Save" . PHP_EOL;

    }
	
	public function po_set_settings($sf){
    	$manual = 0;
        if($this->input->post('manual'))
            $manual = $this->input->post('manual');

        $today = date("Y-m-d");
        $sup = $sf["supplier"];
        $bra = $sf["code"];
        $from = $sf['from'];
        $to = $sf['to'];
        $within_period = 0;
        if(isset($sf["within_period"]))
          if($sf["within_period"] == 1) $within_period = 1;
        $del_date = date('m/d/Y',(strtotime ( '+'.$sf['delivery_day'].' day' , strtotime ( $today ) ) ));
        $valid_date = date('m/d/Y',(strtotime ( '+'.$sf['valid_until'].' day' , strtotime ( $today ) ) ));

        $sel_days = $sf['selling'];
		
		//to double request if friday
		$dayToday = date('l',strtotime(date('Y-m-d')));
		
		 if($dayToday=='Friday'){
			 $sel_days = $sf['selling'] * 2;
		 }
		
		//echo 'start';
        $get_branch_details = $this->auto->main_get_branch_details($bra);
		//echo $get_branch_details;
        $get_branch_details = json_decode(json_encode($get_branch_details),true);
        $result = $this->auto->get_srs_suppliers_details($sup);
        $res = $result[0];
        $zipC = "";
        if($res->zipcode != ""){
            $zipC .= $res->zipcode;
        }
        if($res->country != ""){
            $zipC .= ($res->zipcode != "" ? ",":"").$res->country;
        }
        $supp_det = array("name"=>$res->description,"email"=>$res->email,"terms"=>$res->term_desc,"address"=>$res->address,"city"=>$res->city,"zipC"=>$zipC,"person"=>$res->contactperson,"code"=>$sup);

        $dateDiff = strtotime($from) - strtotime($to); 
        $days_back = ceil($dateDiff/(60*60*24)) + 1;

        $po_setup = array(
            "del_date"=>$del_date,
            "supplier"=>$supp_det,
            "from"=>$from,
            "to"=>$to,
            "selling_days"=>$sel_days,
            "days_back"=>$days_back,
            "manual"=>$manual,
            "valid_date" => $valid_date,
            "within_period" => $within_period,
            "user_id" => $sf["user_id"],
            "auto_po" => $sf["auto_po"],
            "min" => $sf["minimum"],
            "max" => $sf["maximum"]
        );

        $this->session->set_userdata('po_setup',$po_setup);
    }

	public function create_purchase_request(){
        if($this->session->userdata('po_setup')) $settings = $this->session->userdata('po_setup');
        else return true;
        $uom_piece = array('piece', 'pc', 'pcs','pck');
        $from = $settings["from"];
        $to = $settings["to"];
        $supplier_code = $settings["supplier"]["code"];
        $branch_code = BRANCH_USE;
        $supp_items = $this->auto->get_srs_suppliers_item_details(null,$supplier_code);
        $se_items = array();
        foreach ($supp_items as $res) {
            $qty = 0;
            $extended = 0;
            $sugg_po = 0;
        
            $qoh = $res->StockRoom;
            $det['barcode'] = $res->ProductCode;
            $det['description'] = $res->Description;
            $det['uom'] = $res->uom;
            $det['cost'] = $res->cost;
            $det['qty_by'] = $res->reportqty;
            $det['qoh'] = $qoh;
            $det['divisor'] = 0;
            $det['total_sales'] = 0;
            $det['avg_off_take'] = 0;
            $sell_days = $this->auto->get_selling_days_item_by_supplier_branch($branch_code,$supplier_code,$res->ProductCode);

            $det['avg_off_take_x'] = ($sell_days == 0 || $sell_days == null) ? $settings['selling_days'] : $sell_days;
            $det['sell_days'] = $det['avg_off_take_x'];
            $det['sugg_po'] = $sugg_po;
            $det['qty'] = $qty;
            $det['disc1'] = $res->discountcode1;
            $det['disc2'] = $res->discountcode2;
            $det['disc3'] = $res->discountcode3;
            $det['extended'] = $extended;
            $item[$res->ProductID] = $det;
            $se_items[] = $res->ProductID;
        }

        $divs = $this->auto->get_srs_items_po_divisor($from , $to, $se_items);
        $min_purchase_piece =  $this->auto->get_frequency_fresh(null,$branch_code,$supplier_code);
        $case_order_piece = 0;
        $truckLoad = array();

             if(!empty($min_purchase_piece)) {
                $min_purchase_piece = $min_purchase_piece[0];
                $truckLoadId= $min_purchase_piece["id"];
                if($min_purchase_piece["status"] == 1) {
                  $min_purchase_piece = $min_purchase_piece["min_pc"];
                  $truckLoad = $this->auto->getAllTruckLoad($truckLoadId);
                } else $min_purchase_piece = 6;
            } else $min_purchase_piece = 6;

            foreach ($divs as $des) {
                if(isset($item[$des->product_id])){
                    $item[$des->product_id]['divisor'] = $des->divisor;
                    $item[$des->product_id]['total_sales'] = $des->total_sales/$item[$des->product_id]['qty_by'];
                    $avg_off_take = ($item[$des->product_id]['total_sales'])/$item[$des->product_id]['divisor'];
                    $avg_off_take = number_format($avg_off_take, 2, '.', '');//round($avg_off_take,2);

                    $filter_off_take = 0;
                    $filter_sales = 0;
                    $rounding_off = 0;
                    $qoh_before_ordering = .5;

                    $item[$des->product_id]['avg_off_take'] = $avg_off_take;
                    $qoh_ = $item[$des->product_id]['qoh'] > 0 ? $item[$des->product_id]['qoh'] : 0;
                    $sugg_po = (($avg_off_take > $filter_off_take ? $avg_off_take : 0)*
                            $item[$des->product_id]['avg_off_take_x']) - $qoh_;

                    if ($item[$des->product_id]['total_sales'] < $filter_sales) $sugg_po  = 0;
                    $sugg_po = ceil($sugg_po-$rounding_off);
                    if($sugg_po < 0) $sugg_po  = 0;

                    if(in_array(strtolower($item[$des->product_id]['uom']), $uom_piece)){
                        $qty_times = $min_purchase_piece;
                        $sugg_po = $sugg_po/$qty_times;
                        $sugg_po = ceil($sugg_po);
                        $sugg_po = $sugg_po * $qty_times;
                    }
                    else if( isset($truckLoad[$item[$des->product_id]['uom']])  ){
                        $qty_times = $truckLoad[$item[$des->product_id]['uom']];
                        $sugg_po = $sugg_po/$qty_times;
                        $sugg_po = ceil($sugg_po);
                        $sugg_po = $sugg_po * $qty_times;
                    }

                    $qty = $sugg_po;
                    
                    $item[$des->product_id]['sugg_po'] = $sugg_po;
                    $item[$des->product_id]['qty'] = ceil($qty);

                    $extended = $item[$des->product_id]['qty'] * $item[$des->product_id]['cost'];
                    $item[$des->product_id]['extended'] = $extended;

                }
            }
        $discounts = $disc = array();
        $discs = $this->purify_discs($item);
        if (count($discs) > 0)
            $discounts = $this->auto->get_srs_discounts($discs);
        foreach ($discounts as $dis) {
            $disc[$dis->DiscountCode] = array(
                "code" => $dis->DiscountCode,
                "desc" => $dis->Description,
                "amount" => $dis->Amount,
                "percent" => $dis->Percent,
                "plus" => $dis->Plus
            );
        }
       
        foreach ($item as $item_id => $det) {
            $extended = $det['cost'];
            for ($i=1; $i <= 3; $i++) { 
                if($det['disc'.$i] != null){
                    if(isset($disc[$det['disc'.$i]])){
                        $det['disc'.$i] = $disc[$det['disc'.$i]];
                        $extended = $this->calculate_discount($det['disc'.$i],$extended);
                    }
                }
            }
            $extended = $extended * $det['qty'];
            if($extended < 0) $extended = 0;
            $det['extended'] = $extended;
            $item[$item_id] = $det;
        }
        $this->session->set_userdata('po_cart',$item);
    }
	
	public function save_po($sf , $auto_generate = 1, $draft = 1){
		//echo 'save';
        $settings = $this->session->userdata("po_setup");
        $totals = $this->get_po_cart_total(false);
        $net_total = $totals['amount'];
        $within_period  = 0;
        if(isset($settings["within_period"]))
          if($settings["within_period"] == 1) $within_period = 1; 
	  
	  		
        if($net_total > 0 ){
			//echo $net_total;
                    $po_cart = $this->session->userdata("po_cart");
                    $error_msg = null;
                        $sup = $settings['supplier'];
                        $branch = $this->auto->main_get_branch_details(BRANCH_USE);
                       $po_head = array(
                            //"order_no"=>$po_id,
                            "trans_type"=>PR_TRANS,
                            "supplier_id"=>$sup['code'],
                            "supplier_name"=>$sup['name'],
                            "supplier_email"=>$sup['email'],
                            "br_code"=>$branch->code,
                            "delivery_address"=>$branch->address,
                            "delivery_date"=>$this->date2Sql($settings['del_date']),
                            "net_total"=>$net_total,
                            "sales_from"=>$this->date2Sql($settings['from']),
                            "sales_to"=>$this->date2Sql($settings['to']),
                            "selling_days"=>$settings['selling_days'],
                            "manual"=> 0,
                            "valid_date"=> $this->date2Sql($settings['valid_date']),
                            "auto_generate"=> $auto_generate,
                            "draft" => $draft,
                            "within_period" => $within_period
                       ); 
					   
				//echo $sup['code']; 
				//echo 'end1'; 
					  $po_details = array();
                        foreach ($po_cart as $item_id => $row) {
                            if($row['qty'] > 0){
                                $dstr = '';
                                $extended =  $row['cost'];
                                for ($i=1; $i <= 3 ; $i++) { 
                                    $disc = $row['disc'.$i];
                                    if(is_array($disc)){
                                        $dstr .= $disc['code']."=>".$row['qty'] *($deduction = $this->calculate_discount($disc,$extended,true)).",";
                                        $extended -= $deduction;
                                    }
                                }
                                
                                $extended = $row['qty'] * $row['cost'];

                                $discounts_string = (substr($dstr,0,-1));
                                
                                if ($discounts_string == '0')
                                    $discounts_string = '';
                                    
                                $det = array(
                                  //  "order_no"=>$po_id,
                                    "trans_type"=>PR_TRANS,
                                    "stock_id"=>$item_id,
                                    "barcode"=>$row['barcode'],
                                    "description"=>$row['description'],
                                    "unit_id"=>$row['uom'],
                                    "unit_price"=>$row['cost'],
                                    "discounts"=>$discounts_string,
                                    "sgstd_qty"=>$row['sugg_po'],
                                    "ord_qty"=>$row['qty'],
                                    "selling_days"=>$row['sell_days'],
                                );

                               $po_details[] = $det;
                            }
                        }
						
				//echo 'end2'; 
                        $po_head = json_encode($po_head);
						$po_details = json_encode($po_details);
                        if($settings["auto_po"] == 1){
                           $settings["auto_po"] = 1;
                        }
                        $branch_use = BRANCH_NAME;
						$this->auto->auto_save_details($po_head, $po_details, $settings["user_id"], $settings["auto_po"], $branch_use, $sup['code']);
                        $this->session->unset_userdata('po_cart');
                        $this->session->unset_userdata('po_manual_cart');
                //echo 'end3'; 
        }
				//echo 'end4';
    }  

	public function calculate_discount($disc,$amount,$retrunDeduc=false){
        $total = $amount;
        if($disc['percent'] == 1){
            $deduc = ($disc['amount']/100) * $total;
        }
        else{
            $deduc = $disc['amount'];
        }

        if($disc['plus'] == 1){
            $total += $deduc;
        }
        else{
            $total -= $deduc;
        }
        
        if($disc['plus'] == 1)
            $deduc = -$deduc;
        if($retrunDeduc)
            return $deduc;
        else
            return $total;
    }
	
	public function purify_discs($items){
        $discs = array();
        foreach ($items as $item_id => $det) {
            // if($det['disc1'] != null)
                $discs[] = $det['disc1'];            
            // if($det['disc2'] != null)
                $discs[] = $det['disc2'];
            // if($det['disc3'] != null)
                $discs[] = $det['disc3'];
        }  
        $discs = array_unique($discs);
        return array_filter($discs);
    }
	
	public function get_po_cart_total($json=true){
        $po_cart = $this->session->userdata('po_cart');

        $qty = 0;
        $amount = 0;
        foreach ($po_cart as $item_id => $item) {
            $qty += $item['qty'];                      
           
            $amount += $item['extended'];                      
        }
        if($json)
            echo json_encode(array('qty'=>$qty,'amount'=>$amount));
        else 
            return array('qty'=>ceil($qty),'amount'=>$amount); 
    }
	
	public function throw_po(){
		$data = $this->auto->throw_po(); 
		$bool = (count($data) > 0) ? true : false;
		while($bool){
			foreach($data as $row){
				$id = $row["id"];
				unset($row["id"]);
				$this->auto->execute_queue($row, $id);
			} 
			$data = $this->auto->throw_po(); 
			$bool = (count($data) > 0) ? true : false; 
		} 
	}
	
	public   function date2Sql($date){
        return date('Y-m-d', strtotime($date));
     }
	 
	function date_diff2 ($date1, $date2, $period) 
	{

	/* expects dates in the format specified in $DefaultDateFormat - period can be one of 'd','w','y','m'
	months are assumed to be 30 days and years 365.25 days This only works
	provided that both dates are after 1970. Also only works for dates up to the year 2035 ish */

		$date1 = $this->date2sql($date1);
		$date2 =  $this->date2sql($date2);
		list($year1, $month1, $day1) = explode("-", $date1);
		list($year2, $month2, $day2) = explode("-", $date2);

		$stamp1 = mktime(0,0,0, (int)$month1, (int)$day1, (int)$year1);
		$stamp2 = mktime(0,0,0, (int)$month2, (int)$day2, (int)$year2);
		$difference = $stamp1 - $stamp2;

	/* difference is the number of seconds between each date negative if date_ 2 > date_ 1 */

		switch ($period) 
		{
			case "d":
				return (int)($difference / (24 * 60 * 60));
			case "w":
				return (int)($difference / (24 * 60 * 60 * 7));
			case "m":
				return (int)($difference / (24 * 60 * 60 * 30));
			case "s":
				return $difference;
			case "y":
				//return (int)($difference / (24 * 60 * 60 * 365.25));
				return (int)($difference / (24 * 60 * 60 * 365));
			default:
				Return 0;
		}
	}
	
}