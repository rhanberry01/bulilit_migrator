<?php
if ( ! defined('BASEPATH')) exit('No direct script access allowed');
ini_set('MAX_EXECUTION_TIME', -1);
ini_set('mssql.connect_timeout',0);
ini_set('mssql.timeout',0);
set_time_limit(0);  
ini_set('memory_limit', -1);

//client_buffer_max_kb_size = '50240'
//sqlsrv.ClientBufferMaxKBSize = 50240

class Auto extends CI_Controller {
      var $data = array();
        public $rows = array(), $sort = array(), $over_stock_location = 'total_over_stock_report/';
   
   
    public function __construct(){
        parent::__construct();
        date_default_timezone_set('Asia/Manila');
        $real_date = date("Y-m-d");
$to = date("Y-m-d",strtotime($real_date .' -1 days'));
$from = date("Y-m-d",strtotime($to .' -30 days'));
define("FROM", $from);
define("TO", $to);
        $this->load->model("Auto_model", "auto");
    }
     public function get_over_stock_today($from , $to){
    $this->load->library('excel');
    $products = array();
    $vendors = $this->auto->get_user_vendor();
    foreach($vendors as $i => $vendor){
        $vendor_product = $this->auto->get_vendor_products($vendor->vendor);
            if(!empty($vendor_product)) { 
                foreach($vendor_product as $vp) array_push($products,$vp->ProductID);
                    $description = $this->auto->get_vendor( $vendor->vendor);
                    $description_text = $description[0]->description;
                    $this->over_stock_report($products,$from,$to,$description_text,$vendor->vendor);
                       $products = array();
            }  

          // if($i == 100) break;
        }
        $this->create_total_over_stock_report( $from , $to);
            

  }
  public function throw_modified_srp(){
    $res = $this->auto->get_modified_srp();
    $barcodes = array(); 
    foreach ($res as $comstore_det) {
        $barcode =  $comstore_det['barcode'];
        $srp =  $comstore_det['srp'];
       
       $this->auto->update_comstore_srp($barcode,$srp);
       $this->auto->update_srspos_comstore_srp($barcode,$srp);
        
        $barcodes[] =  $barcode;
    }
    $barcodes = implode("','",$barcodes);
    $this->auto->update_comstore_srp_stat($barcodes);
    echo 'comstore srp updated';
  }

  public function update_modified_srp(){
    $ins = $this->auto->insert_new_modifed_srp();
    echo  $ins.PHP_EOL;
    $upd = $this->auto->update_modified_srp();
    echo $upd.PHP_EOL;
    
  }

  public function create_re_order(){
    $res = $this->auto->update_served_qty();
 
  if($res){
        $for_re_order = $this->auto->get_re_order();
      

       foreach ($for_re_order as $res) {        
            $order_id =  $this->auto->get_re_order_id();
           
            $old_ord = $res['old_ord'];

            echo  $order_id.'-'. $old_ord;
            echo $res['customer_name'];
            $header = array(
                "order_id" =>  $order_id,
                "order_date" => date('Y-m-d'),
                "customer_code" => 'RE-ORDER FROM'.$old_ord,
                "customer_name" => $res['customer_name'],
                "total_sales" => $res['total'],
                "with_shipping_fee" => 0,
                "grand_total" => $res['total'],
                "payment_type" => 'COD',
                "order_status" => 0,
                "approved" => 1
            ); 
              $last_inserted_id = $this->auto->insert_franchisee_header($header);
              $res_details = $this->auto->insert_franchisee_details($order_id,$old_ord);
              if($res_details){
                $upd_details  = $this->auto->update_franchisee_details($old_ord,$order_id);  
              }
              

           //die(); 
        }  
    } 
    
  }
  public function transfer_purchases_ms_to_aria(){
    $ms_db = 'branch_nova';
    $aria_db = 'aria_db';

    $ms_res = $this->auto->get_ms_purchases_franchisee($ms_db);

    foreach ($ms_res as $i => $row) {
        //  echo $row->type_no.PHP_EOL;
          $type_no =  $row->type_no;
          $date_ =  $row->date_;
          $tot_amt = $row->tot_amt;
          $OrNum =  $row->OrNum;

          $purchases_result = $this->auto->get_purchases_res($date_,$aria_db,'20', '5450',$type_no);
         // echo round($purchases_result,2).'=='.round($tot_amt,2).PHP_EOL;
          if(!(round($purchases_result,2) == round($tot_amt,2) ) ){
              $this->auto->insert_gl($aria_db,'20', $type_no, $date_,'Purchase MoveNo:'.$type_no.' OR#: '.$OrNum, -$tot_amt,'5450');
              $this->auto->insert_gl($aria_db,'20', $type_no, $date_,'Purchase MoveNo:'.$type_no.' OR#: '.$OrNum, $tot_amt,'2000');
          //   echo "Purchases Has been updated!".PHP_EOL;
          }else{
              echo "Purchases Already Transferred!".PHP_EOL;
          }

          
      }
      echo 'done!';

  }

  public function transfer_ms_sales_to_aria(){
    
    $ms_db = 'branch_nova';
    $aria_db = 'aria_db';

    $ms_res = $this->auto->get_ms_sales_franchisee($ms_db);

    foreach ($ms_res as $i => $row) {
        # code...
        $sales_date =  $row->LogDate;
        $gross_sales =  $row->total;

        ## DEBIT ##
        $sales_collection_gross = $this->auto->get_sales_collection($sales_date,$aria_db,'60', '1060000');
        ## DEBIT ##

         if(!(round($gross_sales,2) == round($sales_collection_gross,2))){

           $get_existed_sales = $this->auto->get_existed_sales_franchisee($sales_date,$aria_db);
            //## delete  existed_sales
             $this->auto->delete_gl_franchisee($sales_date,$aria_db,$get_existed_sales);

            $ref = $this->auto->get_ref_franchisee($aria_db);
            $memo = "Sales";
            $max_type_no = $this->auto->get_next_trans_no_franchisee($aria_db);
            $net_sales = $gross_sales;

            ## insert gl trans gross account 1060000
            $this->auto->insert_gl($aria_db,'60', $max_type_no, $sales_date,'Gross', $gross_sales,'1060000');
            $this->auto->insert_gl($aria_db,'60', $max_type_no, $sales_date,'Cash on hand', -$gross_sales,'1010');

            if($memo != '')
            {
                $this->db_con->add_comments($aria_db,'60', $max_type_no, $sales_date, $memo);
            }
            
                $this->db_con->add_refs($aria_db,'60', $max_type_no,$ref);
                $this->db_con->add_audit_trail($aria_db,'60', $max_type_no, $sales_date,'');
            
        } else {
            echo $row->LogDate.' Already Transfered and Equal'.PHP_EOL;
        }

    }

    echo 'Success'.PHP_EOL;

    
  }

   public function create_transfer_franchise(){
    $transfer_fr_res = $this->auto->get_unprocessed_transfer_fr();

        foreach ($transfer_fr_res as $res) {
            
            echo  $res['TransactionNo'].PHP_EOL;

            $TransactionNo =  $res['TransactionNo'];
            $TerminalNo = $res['TerminalNo'];
            $transfer_fr_id = $res['id'];
            $date_served = $res['date_served'];
            

            $header_tr = array (
                "date_created" => date('Y-m-d'),
                "delivery_date" => $date_served,
                "br_code_out" => 'srsn',
                "aria_type_out" => '70',
                "aria_trans_no_out" => $transfer_fr_id,
                "name_out" => 'NOVALICHES',
                "m_code_out" => 'STO',
                "m_id_out" => $TerminalNo,
                "m_no_out" => $TransactionNo,
                "transfer_out_date" => date('Y-m-d'),
                "br_code_in" => BRANCH_USE,
                "memo_" => LOCAL_BRANCH,
                "aria_type_in" => '0',
                "m_code_in" =>'STI'
            );

            $last_inserted_id = $this->auto->insert_header($header_tr);
            
            $res_trans = $this->auto->get_res_for_trans($TransactionNo,$TerminalNo);
            $det_tr = array();
            foreach ($res_trans as $res_tr) {
               
               $det_tr[] = array ( 
                "transfer_id" => $last_inserted_id,
                "stock_id " => $res_tr['stock_id'],
                "stock_id_2" =>  $res_tr['stock_id_2'],
                "description" =>  $res_tr['description'],
                "barcode" =>  $res_tr['barcode'],
                "uom" =>  $res_tr['uom'],
                "cost" =>  $res_tr['cost'],
                "net_of_vat" =>  $res_tr['net_of_vat'],
                "qty_out" =>  $res_tr['qty_out'],
                "actual_qty_out" =>  $res_tr['actual_qty_out']);


            }
            $this->auto->insert_batch_details($det_tr);
            $this->auto->update_pos_order_stat($transfer_fr_id);
            
        }

   }

   public function over_stock_report($products,$from,$to,$name_head,$value_id=null){
        $se_items =array();
        $counts = 0;
        $supp_items = $this->auto->auto_get_srs_suppliers_item_details2nd($products,$value_id);
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
            $det['vendor_description'] = $res->vendor_description;
            $det['sugg_po'] = $sugg_po;
            $det['qty'] = $qty;
            $det['disc1'] = $res->discountcode1;
            $det['disc2'] = $res->discountcode2;
            $det['disc3'] = $res->discountcode3;
            $det['extended'] = $extended;
            $det['srp'] = $res->srp;
            $item[$res->ProductID] = $det;
            $se_items[] = $res->ProductID;
        }
        $divs = $this->auto->overstock_offtake($from,$to,$se_items, 60);
            foreach ($divs as $des) {
                
                if(isset($item[$des->product_id])){
                     $sales =   $des->total_sales/ $item[$des->product_id]['qty_by'];
                     if($sales == 0) echo $sales.PHP_EOL;

                    /*$divisor = $des->divisor;
                    $sales = $des->total_sales/$item[$des->product_id]['qty_by'];
                    $offtake = $sales/$divisor;
                    $offtake = number_format($offtake, 2, '.', '');//round($avg_off_take,2);*/
                    $qoh = $item[$des->product_id]['qoh'];
                    $qoh = ($qoh < 0) ? 0 : $qoh;
                   /* $qty =  $item[$des->product_id]['qty_by'];
                    $stock_out = floor($qoh/$offtake);*/
                    $cof = round($item[$des->product_id]['cost'], 4);
                   /* $days_to_sell = $qoh / $offtake;
                    $indicator = 60;
                    $floor_days = floor($days_to_sell);*/
                    if(  $sales == 0 && $qoh > 0){
                        echo "zero".PHP_EOL;
                        $this->sort[] = 0;
                            $this->rows[] =  array(
                            $des->product_id,
                             strip_tags(trim($item[$des->product_id]['vendor_description'])),
                         strip_tags($item[$des->product_id]['description']) ,
                             trim($item[$des->product_id]['uom']), 
                             "", 
                             round($qoh,4), 
                             '', 
                             $cof, 
                             '', 
                             '0',
                             "",
                             ""
                        );
                    }
                    else {
                    $off_take_maam_weng =  ($item[$des->product_id]['qoh']  ) -  $sales;
                        if($off_take_maam_weng > 0 )  { 
                            $over_stock = $off_take_maam_weng;
                            $offtake = $sales/ 60;
                            $number = number_format(round($over_stock * $cof,4),2);
                            $days_to_sell = ceil($over_stock / $offtake);
                            $this->sort[] = str_replace(",","",$number);
                                $prod_uom = trim($item[$des->product_id]['uom']);
                                $this->rows[] =  array(
                                    $des->product_id,
                                    strip_tags(trim($item[$des->product_id]['vendor_description'])),
                                    strip_tags($item[$des->product_id]['description']),
                                    trim($item[$des->product_id]['uom']), 
                                    round($offtake,4), 
                                    number_format(round($qoh,4),2), 
                                    round($over_stock,4), 
                                    $cof, 
                                    $number, 
                                    $days_to_sell,
                                    $sales , 
                                    $item[$des->product_id]['qoh']
                                );
                        }
                        
                    }

        
                }
            }

      }
      
      
      public function multiple_create_product_history(){
        $now =   date('Y-m-d');
        $past_date = date('Y-m-d', strtotime('-5 days'));
        $dates = $this->getDatesFromRange($past_date, $now);
            foreach ($dates as $date){
                echo "Create Product History ".$date.PHP_EOL;
                $this->auto->delete_product_history($date);
                $record = $this->auto->get_item_total_sales($date); 
                $this->auto->insert_prod_history_summary_sales($record, $date);
                $wholesale = $this->auto->update_excluded_wholesale($date);
                if(count($wholesale) > 0)  echo "Wholesale Update ".$date.PHP_EOL;
                $this->auto->update_wholesale($wholesale, $date);
                echo "success".PHP_EOL;
            }
            echo "done!".PHP_EOL;
    }

  public function create_total_over_stock_report( $from , $to){
        $sort = $this->sort;
        $rows = $this->rows;
       
        $month_name = $from.'_'.$to;
        $objPHPExcel = new PHPExcel();
        $objPHPExcel->setActiveSheetIndex(0);
        $BStyle = array(
          'borders' => array(
            'allborders' => array(
              'style' => PHPExcel_Style_Border::BORDER_THIN
            )
          )
        );
 $i = 1;
                 $objPHPExcel->getActiveSheet()->setCellValue('A'.$i, "Product ID");
                    $objPHPExcel->getActiveSheet()->setCellValue('B'.$i, "Vendor");
                     $objPHPExcel->getActiveSheet()->setCellValue('C'.$i, "Description");
                      $objPHPExcel->getActiveSheet()->setCellValue('D'.$i, "UOM");
                       $objPHPExcel->getActiveSheet()->setCellValue('E'.$i, "Offtake");
                        $objPHPExcel->getActiveSheet()->setCellValue('F'.$i, "Total Inventory");
                         $objPHPExcel->getActiveSheet()->setCellValue('G'.$i, "Over Stock");
                          $objPHPExcel->getActiveSheet()->setCellValue('H'.$i, "Selling Price");
                           $objPHPExcel->getActiveSheet()->setCellValue('I'.$i, "Total Cost");
                            $objPHPExcel->getActiveSheet()->setCellValue('J'.$i, "Day To Sell");

 $objPHPExcel->getActiveSheet()->setCellValue('L'.$i, "Sales");
                            $objPHPExcel->getActiveSheet()->setCellValue('M'.$i,"Inventory");
                foreach($rows as $key => $row){
                        $i = $key + 2;
                    $objPHPExcel->getActiveSheet()->setCellValue('A'.$i, $row[0]);
                    $objPHPExcel->getActiveSheet()->setCellValue('B'.$i, $row[1]);
                     $objPHPExcel->getActiveSheet()->setCellValue('C'.$i, $row[2]);
                      $objPHPExcel->getActiveSheet()->setCellValue('D'.$i, $row[3]);
                       $objPHPExcel->getActiveSheet()->setCellValue('E'.$i, $row[4] );
                        $objPHPExcel->getActiveSheet()->setCellValue('F'.$i, $row[5]);
                         $objPHPExcel->getActiveSheet()->setCellValue('G'.$i, $row[6]);
                          $objPHPExcel->getActiveSheet()->setCellValue('H'.$i, $row[7]);
                           $objPHPExcel->getActiveSheet()->setCellValue('I'.$i, $row[8]);
                            $objPHPExcel->getActiveSheet()->setCellValue('J'.$i, $row[9]);
                            $objPHPExcel->getActiveSheet()->setCellValue('L'.$i, $row[10]);
                            $objPHPExcel->getActiveSheet()->setCellValue('M'.$i, $row[11]);
                }
        $objPHPExcel->getActiveSheet()->getStyle('A1:J1')->applyFromArray($BStyle);
        $objPHPExcel->getActiveSheet()->setTitle('Simple');
        $objPHPExcel->setActiveSheetIndex(0);
        $objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel2007');
        $title = BRANCH_NAME."_TOTAL_Over_Stock_".$month_name;
        echo $title.PHP_EOL;
        $objWriter->save("total_over_stock_report/".$title.'.xlsx');           
    }

public function show_branch(){

        echo BRANCH_USE. " " . BRANCH_NAME; sleep(10);
    }
    public function generate_product_history($from, $to){

    $day = 86400; // Day in seconds  
        $format = 'Y-m-d'; // Output format (see PHP date funciton)  
        $sTime = strtotime($from); // Start as time  
        $eTime = strtotime($to); // End as time  
        $numDays = round(($eTime - $sTime) / $day) + 1;  
        $days = array();  

        for ($d = 0; $d < $numDays; $d++) {  
            $days[] = date($format, ($sTime + ($d * $day)));  
        } 

        foreach($days as $date) $this->create_product_history($date);
  }

    public   function date2Sql($date){
        return date('Y-m-d', strtotime($date));
     }

     public function generate_everyday(){
        $this->create_product_history();
       // $this->index();
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

    

    public function auto_generate_pr($sf){
        

 echo $sf["frequency"].PHP_EOL;

        $this->po_set_settings($sf);
        echo "Set Settings" . PHP_EOL;  
        $this->create_purchase_request();
        echo "Set Form" . PHP_EOL;  
        $this->save_po($sf);
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
        $get_branch_details = $this->auto->main_get_branch_details($bra);
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
        echo  $supplier_code.'<_'.PHP_EOL;
        $branch_code = BRANCH_USE;
        $divisor_  = '30';
        $new_branch_checker  = $this->auto->must_have_seven_days_sale();
        if($new_branch_checker < 30){
            $days_ = $to .' -'.$new_branch_checker.' days';
            $from = date("Y-m-d",strtotime($days_));
            $divisor_  = $new_branch_checker;
          
        }
      
        $supp_items = $this->auto->get_srs_suppliers_item_details(null,$supplier_code);
        $se_items = array();
		$lucky_me = array('4807770270017',
						'4807770270024',
						'4807770273698',
						'4807770274305',
						'4807770273674',
						'4807770273704',
						'4807770273711'
						);
		$load = array(
		'999000729755',
		'999000729762',
		'999000729779',
		'999000729786',
		'999000729793',
		'999000729809',
		'999000729816',
		'999000740187',
		'999000788356',
		'999000788363',
		'999000788370',
		'999000788387',
		'999000788394',
		'999000788400',
		'999000788417',
		'999000788424',
		'999000788431',
		'999000788448',
		'999000788455',
		'999000788462',
		'999000788479',
		'999000788486',
		'999000788493',
		'999000788509',
		'999000788516'
		);
        echo '>>>>>>>>>'.count($supp_items).'<num';
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
            
			$ptage =   $res->srs_percentage;

            if($res->ProductCode == '915455'){
                echo PHP_EOL.$ptage.'---**'. PHP_EOL;
            }
			
            if (in_array($res->ProductCode,$lucky_me)){
				$ptage = 0.99;
			}
			
			if (in_array($res->ProductCode,$load)){
				$ptage = 0.99;
			}
           // $sell_days = $this->auto->get_selling_days_item_by_supplier_branch($branch_code,$supplier_code,$res->ProductCode);
           
           $det['LevelField1Code'] = $res->LevelField1Code;
           $det['srs_percentage'] = $ptage;
          
            $det['avg_off_take_x'] = ($divisor_ < 30 ? 4 : 7); // ($sell_days == 0 || $sell_days == null) ? $settings['selling_days'] : $sell_days;
            $det['sell_days'] = $det['avg_off_take_x'];
            $det['sugg_po'] = $sugg_po;
            $det['qty'] = $qty;
            $det['disc1'] = $res->discountcode1;
            $det['disc2'] = $res->discountcode2;
            $det['disc3'] = $res->discountcode3;
            $det['extended'] = $extended;
            $item[$res->ProductID] = $det;
            $se_items[] = $res->ProductID;
            $se_barcodes[] = $res->ProductCode;
        }
        $fritems = "'".implode("','", $se_barcodes)."'";   
        $frcost =  $this->auto->get_franchise_cost($fritems);
        $divs = $this->auto->get_srs_items_po_divisor($from , $to, $se_items,$divisor_ );
        $min_purchase_piece =  $this->auto->get_frequency(null,$branch_code,$supplier_code);
        $case_order_piece = 0;
        $truckLoad = array();

        //get franchisee cost
       // echo var_dump($frcost);
     
        $FranchiseeDetails= array();
        foreach ($frcost as $fr) {
            
            $detfr['costfr'] = $fr['CostOfSales'];
            $detfr['vendorfr'] = $fr['VendorCode'];
            $detfr['prodid'] = $fr['ProductID'];
            $FranchiseeDetails[$fr['Barcode']] = $detfr;
        }
       
     //   echo var_dump($FranchiseeDetails);
   //  die();
             $ls_vendor =  array('TASH001','EMDIIN001','MNOI002');
            foreach ($divs as $des) {
              //  echo 'sss';
                if(isset($item[$des->product_id])){

                    $item[$des->product_id]['divisor'] = $des->divisor;
                  //  echo  $item[$des->product_id]['divisor'].PHP_EOL;
                    $item[$des->product_id]['total_sales'] = $des->total_sales; //  /$item[$des->product_id]['qty_by'] 
                   // echo $des->total_sales.'/'.$item[$des->product_id]['qty_by'].PHP_EOL;
                    $avg_off_take = ($item[$des->product_id]['total_sales'])/$item[$des->product_id]['divisor'];
                   // echo '('.$item[$des->product_id]['total_sales'].')/'.$item[$des->product_id]['divisor'].PHP_EOL;
                    $avg_off_take = number_format($avg_off_take, 3, '.', '');//round($avg_off_take,2);
                   // echo 'ave:'.$avg_off_take;

                    $filter_off_take = 0;
                    $filter_sales = 0;
                    $rounding_off = 0;
                    $qoh_before_ordering = .5;

                    $item[$des->product_id]['avg_off_take'] = $avg_off_take;
                    $qoh_ = $item[$des->product_id]['qoh'] > 0 ? $item[$des->product_id]['qoh'] : 0;

                    $qoh_ = number_format($qoh_, 3, '.', '');

                    $sugg_po = (($avg_off_take > $filter_off_take ? $avg_off_take : 0)*
                            $item[$des->product_id]['avg_off_take_x']) - $qoh_;
                    
                    if ($item[$des->product_id]['total_sales'] < $filter_sales) $sugg_po  = 0;
                    $sugg_po = ceil($sugg_po-$rounding_off);
                    if($sugg_po < 0) {
                        $sugg_po  = 0;
                    }
                    //echo 'sugg:'.$sugg_po;


                    $qty = $sugg_po / $item[$des->product_id]['qty_by'];
                    
                    $srs_percentage = $item[$des->product_id]['srs_percentage'];
                   
                    if(in_array($FranchiseeDetails[$item[$des->product_id]['barcode']]['vendorfr'],$ls_vendor)){
                        $srs_percentage = 0.995;
                    }
                  
                   // echo  $srs_percentage;
                   $item[$des->product_id]['comp_history'] ='Total Sales: '.$item[$des->product_id]['total_sales'].' Ave Offtake: '.$avg_off_take.' QOH: '.$qoh_.' *Suggested Qty is based from '.$from.' to '.$to.'and '.$item[$des->product_id]['avg_off_take_x'].' selling days.' ;
                    $item[$des->product_id]['cost'] = ($FranchiseeDetails[$item[$des->product_id]['barcode']]['costfr'] /  $srs_percentage ) * $item[$des->product_id]['qty_by'];
                   
                    $item[$des->product_id]['from'] = $from;
                    $item[$des->product_id]['to'] = $to;

                    $item[$des->product_id]['sugg_po'] = ceil($qty);
                    $item[$des->product_id]['qty'] = ceil($qty);
                    $item[$des->product_id]['cost_percentage']  = $srs_percentage;

                    $extended = $item[$des->product_id]['qty'] * $item[$des->product_id]['cost'];
                    $item[$des->product_id]['extended'] = $extended;

                }
            }
      
        $this->session->set_userdata('po_cart',$item);
      
    }

     public function index(){
       $user = null;
         echo date("Y-m-d h:i:s").PHP_EOL;
         $supplier = null;
         //$supplier = "COINFD001";
         $excluded_vendors=array();
        $excluded_vendors = $this->auto->get_frequency_excluded();
        //$excluded_vendors=implode(",", $excluded_vendors);
        
        $supplier_frequency = $this->auto->get_frequency($user,BRANCH_USE,$supplier,array(),"1","1", null, null, "1",$excluded_vendors);
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
            echo $sf["sf_supplier"].'->>'.PHP_EOL; // die();
            if($sf["sf_supplier"]=='SANROB001'){
                $sf["from"] = $from;
                $sf["to"] = $to;
                echo "bulilit..pr".PHP_EOL;
                $this->auto_generate_pr($sf); 
            }else if($po_schedule == 'F4' && $day_chosen == $dayToday){
                $sf["from"] = $from;
                $sf["to"] = $to;
                $this->auto_generate_pr($sf);   
            }
            else if( in_array($po_schedule,$special_array) && $day_chosen == $dayToday)
            {
                $check_week = 0;

                for($x=1; $x< $day_last; $x++){
                    
                    if(strlen($x) != 2)
                        $append = '0'.$x;
                    else
                        $append = $x;

                    $day_pick = date('Y-m-'.$append);
                    
                    $dayCheck = date('l',strtotime($day_pick));

                    if($dayCheck == $day_chosen){

                        $check_week++;
                        if($po_schedule == "F1" and $check_week == 1){
                             $first_date = $day_pick;
                            break;
                        }
                        else if($po_schedule == "F5" and $check_week == 2){
                             $first_date = $day_pick;
                            break;
                        }
                        else if($po_schedule == "F6" and $check_week == 3){
                             $first_date = $day_pick;
                            break;
                        }
                        else if($po_schedule == "F7" and $check_week == 4){
                             $first_date = $day_pick;
                            break;
                        }
                    }
                }
               
                if($first_date == $date_today)
                {
                    $sf["from"] = $from;
                    $sf["to"] = $to;
                    $this->auto_generate_pr($sf);
                }
            }
            else if($po_schedule == 'F2'){
                $po_to = date("Y-m-d", strtotime($date_today));
                $po_from = date("Y-m-d", strtotime($date_created));
                if($po_to > $po_from){
                    $detector = $this->date_diff2($po_to, $po_from, "d");
                    if($detector != 0){
                        $determine = (int) ($detector / 7);
                        if( $determine % 2 == 0 && $day_chosen == $dayToday){
                            $sf["from"] = $from;
                            $sf["to"] = $to;
                            $this->auto_generate_pr($sf); 
                        }
                    }
                }
            } 
        }
        //$this->throw_po();
        echo date("Y-m-d h:i:s").PHP_EOL;
       
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

    public function save_po($sf , $auto_generate = 1, $draft = 1){
      
        $settings = $this->session->userdata("po_setup");
        $totals = $this->get_po_cart_total(false);
        $net_total = $totals['amount'];
        $within_period  = 0;
        if(isset($settings["within_period"]))
          if($settings["within_period"] == 1) $within_period = 1; 
        if($totals['qty'] > 0 ){
                    $po_cart = $this->session->userdata("po_cart");
                    $error_msg = null;
                        $sup = $settings['supplier'];
                        $branch = $this->auto->main_get_branch_details(BRANCH_USE);
                        $fr_id = $this->auto->get_max_fr_id();
                        echo $fr_id.'********';
                        echo $branch;

                        $header = array(
                            "order_id" => $fr_id,
                            "order_date" => date('Y-m-d'),
                            "customer_code" =>'',
                            "customer_name" => BRANCH_NAME,
                            "total_sales" => $net_total,
                            "with_shipping_fee" => 0,
                            "grand_total" => $net_total,
                            "payment_type" => 'cod',
                            "order_status" => 0
                          
                        );    


                      $po_details = array();
                        foreach ($po_cart as $item_id => $row) {
                            if($row['qty'] > 0){

                                $dstr = '';
                                $extended =  $row['cost'];
                                $extended = $row['qty'] * $row['cost'];

                                $discounts_string = (substr($dstr,0,-1));
                                
                                if ($discounts_string == '0')
                                    $discounts_string = '';
                                
                                $descripiton_ =preg_replace('/[^A-Za-z0-9\-]/', '', $row['description']);  
                                
                                $det = array(
                                    "order_id"=>$fr_id,
                                    "barcode"=>$row['barcode'],
                                    "description"=>$descripiton_,
                                    "qty"=>$row['sugg_po'],
                                    "srp"=> $row['cost'] , //* $row['cost_percentage']
                                    "category"=>$row['LevelField1Code'],
                                    "subtotal"=>$row['sugg_po'] * $row['cost'], //  ($row['cost']*$row['cost_percentage'])
                                    "comp_history"=>$row['comp_history']
                                );

                               $po_details[] = $det;
                            }
                        }
                        
                       $this->auto->insert($header);
                       $this->auto->insert_batch($po_details);
                        

                        echo 'done!';

                        if($settings["auto_po"] == 1){
                           $settings["auto_po"] = 1;
                        }
                        $branch_use = BRANCH_NAME;

                        $this->session->unset_userdata('po_cart');
                        $this->session->unset_userdata('po_manual_cart');
                   
        }
    }  

   
    function date_diff(DateTime $date1, DateTime $date2) {
    
    $diff = new DateInterval();
    
    if($date1 > $date2) {
      $tmp = $date1;
      $date1 = $date2;
      $date2 = $tmp;
      $diff->invert = 1;
    } else {
      $diff->invert = 0;
    }

    $diff->y = ((int) $date2->format('Y')) - ((int) $date1->format('Y'));
    $diff->m = ((int) $date2->format('n')) - ((int) $date1->format('n'));
    if($diff->m < 0) {
      $diff->y -= 1;
      $diff->m = $diff->m + 12;
    }
    $diff->d = ((int) $date2->format('j')) - ((int) $date1->format('j'));
    if($diff->d < 0) {
      $diff->m -= 1;
      $diff->d = $diff->d + ((int) $date1->format('t'));
    }
    $diff->h = ((int) $date2->format('G')) - ((int) $date1->format('G'));
    if($diff->h < 0) {
      $diff->d -= 1;
      $diff->h = $diff->h + 24;
    }
    $diff->i = ((int) $date2->format('i')) - ((int) $date1->format('i'));
    if($diff->i < 0) {
      $diff->h -= 1;
      $diff->i = $diff->i + 60;
    }
    $diff->s = ((int) $date2->format('s')) - ((int) $date1->format('s'));
    if($diff->s < 0) {
      $diff->i -= 1;
      $diff->s = $diff->s + 60;
    }
    
    $start_ts   = $date1->format('U');
    $end_ts   = $date2->format('U');
    $days     = $end_ts - $start_ts;
    $diff->days  = round($days / 86400);
    
    if (($diff->h > 0 || $diff->i > 0 || $diff->s > 0))
      $diff->days += ((bool) $diff->invert)
        ? 1
        : -1;

    return $diff;
    
  }  

 public function getDatesFromRange($start, $end, $format = 'Y-m-d') {
      
    // Declare an empty array
    $array = array();
      
    // Variable that store the date interval
    // of period 1 day
    $interval = new DateInterval('P1D');
  
    $realEnd = new DateTime($end);
    $realEnd->add($interval);
  
    $period = new DatePeriod(new DateTime($start), $interval, $realEnd);
  
    // Use loop to store date into array
    foreach($period as $date) {                 
        $array[] = $date->format($format); 
    }
  
    // Return the array elements
    return $array;
}



  public function create_product_history($date = null){

    if($date==null) $date = date("Y-m-d", strtotime("-1 day"));
    echo "Create Product History ".$date.PHP_EOL;
    $this->auto->delete_product_history($date);
    $record = $this->auto->get_item_total_sales($date); 
    $this->auto->insert_prod_history_summary_sales($record, $date);
    $wholesale = $this->auto->update_excluded_wholesale($date);
    if(count($wholesale) > 0)  echo "Wholesale Update ".$date.PHP_EOL;
    $this->auto->update_wholesale($wholesale, $date);
  }


   public function generate_out_of_stock($today = null)
   {
    $from = FROM; 
    $to = TO;
    if($today == null){
    $today = date("Y-m-d");
    $real_date = $today;
    $to = date("Y-m-d",strtotime($real_date .' -1 days'));
    $from = date("Y-m-d",strtotime($to .' -29 days'));
    }
        $vendorcode = $this->auto->get_default_vendor();
        foreach($vendorcode as $vendor)
        {
            $vendor_products = array();
            $products = $this->auto->get_vendor_products($vendor["vendorcode"]);
            foreach($products as $product) array_push($vendor_products, $product->ProductID);
             echo 'Creating data for '.$vendor["vendorcode"].' ..... ' . PHP_EOL;
            $this->out_of_stock_report($vendor_products,$from,$to,$vendor["vendorcode"],$today, $vendor["user_id"]);
            $products = array();
            echo 'Output data '.$vendor["vendorcode"].'.....' . PHP_EOL;
        }
       
   }

   public function out_of_stock_report($products,$from,$to,$value_id=null,$today, $purch_id=null){ 
        $se_items =array();
        $sort = array();
        $rows = array();
        $counts = 0;
        $supp_items = $this->auto->auto_get_srs_suppliers_item_details2nd($products,$value_id);
        $se_items = array();
        foreach ($supp_items as $res) {
            $qty = 0;
            $extended = 0;
            $sugg_po = 0;
            $qoh = $res->StockRoom;
            $det['barcode'] = $res->ProductCode;
            $det['levelField'] = $res->LevelField1Code;
            $det['description'] = $res->Description;
            $det['uom'] = $res->uom;
            $det['cost'] = $res->cost;
            $det['qty_by'] = $res->reportqty;
            $det['qoh'] = $qoh;
            $det['divisor'] = 0;
            $det['total_sales'] = 0;
            $det['avg_off_take'] = 0;
            $det['vendor_description'] = $res->vendor_description;
            $det['vendor_code'] = $res->VendorCode;
            $det['sugg_po'] = $sugg_po;
            $det['qty'] = $qty;
            $det['disc1'] = $res->discountcode1;
            $det['disc2'] = $res->discountcode2;
            $det['disc3'] = $res->discountcode3;
            $det['extended'] = $extended;
            $det['srp'] = $res->srp;
            $item[$res->ProductID] = $det;
            $se_items[] = $res->ProductID;
        }
   
        //$divs = $this->auto->get_srs_items_po_divisor($from,$to,$se_items);
        
            /*foreach ($divs as $des) {
                
                if(isset($item[$des->product_id])){
                    $divisor = $des->divisor;
                    $sales = $des->total_sales/$item[$des->product_id]['qty_by'];
                    $offtake = $sales/$divisor;
                    $offtake = number_format($offtake, 2, '.', '');//round($avg_off_take,2);
                      $qoh = $item[$des->product_id]['qoh'];
                      $qoh = ($qoh < 0) ? 0 : $qoh;
                       $qty =  $item[$des->product_id]['qty_by'];
                       if($offtake == 0) continue;
                    $stock_out = floor($qoh/$offtake);
                if ( $stock_out < 7 && $stock_out != 0) {
                        
                        $ord_qty = 0;
                        $delivery_date = $this->auto->get_out_of_stock_po_date($des->product_id,BRANCH_USE);
                        $day_forcast = 7 - $stock_out;
                        $status = "";
                        if(empty($delivery_date))  continue;
                        if($today == $delivery_date->delivery_date) continue;
                            $day_os = date('Y-m-d',strtotime("+".$stock_out." day"));

                            if(!empty($delivery_date) && $delivery_date->delivery_date >= $today) {
                                if($day_os <= $delivery_date->delivery_date){
                                     $ord_qty = $delivery_date->ord_qty;
                                     $status = $delivery_date->delivery_date;
                                     $day_forcast =   (strtotime($delivery_date->delivery_date) - strtotime($day_os)) / (60 * 60 * 24);
                                     $status = $delivery_date->delivery_date;
                                } else if ($day_os > $delivery_date->delivery_date) {
                                    $ord_qty = $delivery_date->ord_qty;
                                    $status = $delivery_date->delivery_date;
                                    $day_forcast =  0;
                                    continue;
                                }
                            }
                            $cof = round($item[$des->product_id]['srp'], 4);
                            $days = $stock_out.' Days';
                            $projected = $day_forcast * $cof * $offtake;
                            $projected = round($projected, 4);
                            $sort[] = str_replace(",","",$projected);
                            $loss_sales = 0;
                            if($day_forcast == 7 && $status == "" && $offtake > 0 ){
                                $loss_sales =  $projected/7;
                            }

                            if($delivery_date->delivery_date != date('Y-m-d') )
                            $rows[] = array(
                                "product_id" => $des->product_id, 
                                "vendor_description" => strip_tags(trim($item[$des->product_id]['vendor_description'])),
                                "product_description" => strip_tags($item[$des->product_id]['description']),
                                "uom" => trim($item[$des->product_id]['uom']),
                                "cof" =>$cof, 
                                "offtake" => round($offtake, 4), 
                                "qoh" => round($qoh, 4), 
                                "days" => $days, 
                                "day_forcast" => $day_forcast, 
                                "projected" => $projected,
                                "status" => $status,
                                "order_qty" =>$ord_qty,
                                "date_today" => $today,
                                "branch" => BRANCH_USE,
                                "levelfield" => trim($item[$des->product_id]['levelField']),
                                "vendorcode" => strip_tags($item[$des->product_id]['vendor_code']),
                                "lost_sales" => $loss_sales

                            );
                    }     
                    
                }
            }*/

             $divs = $this->auto->get_srs_items_po_divisor($from,$to,$se_items);
            foreach ($divs as $des) {
                
                if(isset($item[$des->product_id])){
                    $divisor = $des->divisor;
                    $sales = $des->total_sales/$item[$des->product_id]['qty_by'];
                    $offtake = $sales/$divisor;
                    $offtake = number_format($offtake, 2, '.', '');//round($avg_off_take,2);
                      $qoh = $item[$des->product_id]['qoh'];
                      $qoh = ($qoh < 0) ? 0 : $qoh;
                       $qty =  $item[$des->product_id]['qty_by'];

                    if($qoh > 0 )$stock_out = floor($qoh/$offtake);
                    else $stock_out = 0;

              

                if ( $stock_out < 7 && $offtake != "0.00"/*&& $stock_out != 0*/) {
                        
                        $ord_qty = 0;
                        $delivery_date = $this->auto->get_out_of_stock_po_date($des->product_id,BRANCH_USE);
                        $day_forcast = 7 - $stock_out;
                        $status = "";
                        if($today == $delivery_date->delivery_date) continue;
                            $day_os = date('Y-m-d',strtotime("+".$stock_out." day"));

                            if(!empty($delivery_date) && $delivery_date->delivery_date >= $today) {
                                if($day_os <= $delivery_date->delivery_date){
                                     $ord_qty = $delivery_date->ord_qty;
                                     $status = $delivery_date->delivery_date;
                                     $day_forcast =   (strtotime($delivery_date->delivery_date) - strtotime($day_os)) / (60 * 60 * 24);
                                     $status = $delivery_date->delivery_date;
                                } else if ($day_os > $delivery_date->delivery_date) {
                                    $ord_qty = $delivery_date->ord_qty;
                                    $status = $delivery_date->delivery_date;
                                    $day_forcast =  0;
                                    continue;
                                }
                            }
                            $cof = round($item[$des->product_id]['srp'], 4);
                            $days = $stock_out.' Days';
                            $projected = $day_forcast * $cof * $offtake;
                            $projected = round($projected, 4);
                            $sort[] = str_replace(",","",$projected);
                            $loss_sales = 0;
                            if($day_forcast == 7 && $status == "" && $offtake > 0 ){
                                $loss_sales =  $projected/7;
                            }
                            if($delivery_date->delivery_date != date('Y-m-d') )
                            $rows[] = array(
                                "product_id" => $des->product_id, 
                                "vendor_description" => strip_tags(trim($item[$des->product_id]['vendor_description'])),
                                "product_description" => strip_tags($item[$des->product_id]['description']),
                                "uom" => trim($item[$des->product_id]['uom']),
                                "cof" =>$cof, 
                                "offtake" => round($offtake, 4), 
                                "qoh" => round($qoh, 4), 
                                "days" => $days, 
                                "day_forcast" => $day_forcast, 
                                "projected" => $projected,
                                "status" => $status,
                                "order_qty" =>$ord_qty,
                                "date_today" => $today,
                                "branch" => BRANCH_USE,
                                "levelfield" => trim($item[$des->product_id]['levelField']),
                                "vendorcode" => strip_tags($item[$des->product_id]['vendor_code']),   
                                "lost_sales" => $loss_sales
                            );
                    }     
                    
                }
            }
         
        $this->auto->insert_out_of_stock($rows);   
    } 

    public function throw_os(){
       $data = $this->auto->throw_os(); 
       $bool = (count($data) > 0) ? true : false;
       while($bool){  
         foreach($data as $row){
            $id = $row["id"];
            unset($row["id"]);
            unset($row["throw"]);
            $this->auto->execute_queue($row, $id, "out_of_stock");
        }   
       $data = $this->auto->throw_os(); 
       $bool = (count($data) > 0) ? true : false;
    }
}


}
