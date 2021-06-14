<?php
defined('BASEPATH') OR exit('No direct script access allowed');
ini_set('MAX_EXECUTION_TIME', -1);
ini_set('mssql.connect_timeout',0);
ini_set('mssql.timeout',0);
set_time_limit(0);  
ini_set('mssql.textlimit',2147483647);
ini_set('mssql.textsize',2147483647);
ini_set('memory_limit', -1);
date_default_timezone_set('Asia/Manila');
class Welcome extends CI_Controller {

	/**
	 * Index Page for this controller.
	 *
	 * Maps to the following URL
	 * 		http://example.com/index.php/welcome
	 *	- or -
	 * 		http://example.com/index.php/welcome/index
	 *	- or -
	 * Since this controller is set as the default controller in
	 * config/routes.php, it's displayed at http://example.com/
	 *
	 * So any other public methods not prefixed with an underscore will
	 * map to /index.php/welcome/<method_name>
	 * @see https://codeigniter.com/user_guide/general/urls.html
	 */


	public function index($bool = 1,$batch = null){
		while(true){
			$this->load->model("Datacenter_model", "dc");
			$branches = $this->dc->get_batch_db($batch);
			$branches = $this->dc->distinct_branch();
			foreach($branches as $branch){
				$branch_details = $this->dc->branch_details($branch["branch_code"]);
				/*if($bool != 1) {
						echo $branch["branch_code"].PHP_EOL;
						echo "SLEEP".PHP_EOL;
						sleep(20); 
						echo "AWAKE".PHP_EOL;
				}
				else echo "NONE";*/
				$data = $this->dc->getBranchesUpdate($branch["branch_code"]);
				//print_r($data); exit;
				foreach($data as $row){
					$bool = $this->dc->throw_line($row, $branch_details);
					if(!$bool) break;
				}
			}
			sleep(30);
		}


	}



	public function index_($bool = 1)
	{
		while(true){
			$this->load->model("Datacenter_model", "dc");
			$branches = $this->dc->distinct_branch();
			foreach($branches as $branch){
				$branch_details = $this->dc->branch_details($branch["branch_code"]);
				/*if($bool != 1) {
						echo $branch["branch_code"].PHP_EOL;
						echo "SLEEP".PHP_EOL;
						sleep(20); 
						echo "AWAKE".PHP_EOL;
				}
				else echo "NONE";*/
				$data = $this->dc->getBranchesUpdate($branch["branch_code"]);
				//print_r($data); exit;
				foreach($data as $row){
					$bool = $this->dc->throw_line($row, $branch_details);
					if(!$bool) break;
				}
			}
			sleep(30);
		}
	}

}
