<?php
if ( ! defined('BASEPATH')) exit('No direct script access allowed');
ini_set('MAX_EXECUTION_TIME', -1);
ini_set('mssql.connect_timeout',0);
ini_set('mssql.timeout',0);
set_time_limit(0);  
ini_set('mssql.textlimit',2147483647);
ini_set('mssql.textsize',2147483647);
ini_set('memory_limit', -1);

//client_buffer_max_kb_size = '50240'
//sqlsrv.ClientBufferMaxKBSize = 50240

class MainUpdates extends CI_Controller {
      var $data = array();
        public $rows = array(), $sort = array(), $over_stock_location = 'total_over_stock_report/';
   
    public function __construct(){
		parent::__construct();
        date_default_timezone_set('Asia/Manila');
       $this->load->model("Main_model", "main");
    }
	
	public function index(){
		while(true){
			$data = $this->main->getData();
			foreach($data as $row) $this->main->execute($row);
			sleep(10);
		}
	}


}
