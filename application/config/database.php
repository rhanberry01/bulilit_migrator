<?php
defined('BASEPATH') OR exit('No direct script access allowed');

$active_group = 'default';
$query_builder = TRUE;

$db['default'] = array(
	'dsn'	=> '',
	'hostname' => 'localhost',
	'username' => 'root',
	'password' => 'srsnova',
	'database' => 'migration',
	'dbdriver' => 'mysqli',
	'dbprefix' => '',
	'pconnect' => FALSE,
	'db_debug' => (ENVIRONMENT !== 'development'),
	'cache_on' => FALSE,
	'cachedir' => '',
	'char_set' => 'utf8',
	'dbcollat' => 'utf8_general_ci',
	'swap_pre' => '',
	'encrypt' => FALSE,
	'compress' => FALSE,
	'stricton' => FALSE,
	'failover' => array(),
	'save_queries' => TRUE
);

$db['po'] = array(
	'dsn'	=> '',
	'hostname' => '192.168.0.91',
	'username' => 'root',
	'password' => 'srsnova',
	'database' => 'srs',
	'dbdriver' => 'mysqli',
	'dbprefix' => '',
	'pconnect' => FALSE,
	'db_debug' => (ENVIRONMENT !== 'development'),
	'cache_on' => FALSE,
	'cachedir' => '',
	'char_set' => 'utf8',
	'dbcollat' => 'utf8_general_ci',
	'swap_pre' => '',
	'encrypt' => FALSE,
	'compress' => FALSE,
	'stricton' => FALSE,
	'failover' => array(),
	'save_queries' => TRUE
);

$db['main_po'] = array(
	'dsn'	=> '',
	'hostname' => '192.168.0.56',
	'username' => 'root',
	'password' => '',
	'database' => 'srs',
	'dbdriver' => 'mysqli',
	'dbprefix' => '',
	'pconnect' => FALSE,
	'db_debug' => (ENVIRONMENT !== 'development'),
	'cache_on' => FALSE,
	'cachedir' => '',
	'char_set' => 'utf8',
	'dbcollat' => 'utf8_general_ci',
	'swap_pre' => '',
	'encrypt' => FALSE,
	'compress' => FALSE,
	'stricton' => FALSE,
	'failover' => array(),
	'save_queries' => TRUE
);

$db['branch_nova']['hostname'] = "localhost";
$db['branch_nova']['username'] = 'markuser';
$db['branch_nova']['password'] = 'tseug';
$db['branch_nova']['database'] = SRS_BRANCH_DATABASE;
//$db['branch_nova']['dbdriver'] = 'sqlsrv';
$db['branch_nova']['dbdriver'] = 'mssql';
$db['branch_nova']['dbprefix'] = '';
$db['branch_nova']['pconnect'] = TRUE;
$db['branch_nova']['db_debug'] = FALSE;
$db['branch_nova']['cache_on'] = FALSE;
$db['branch_nova']['cachedir'] = '';
$db['branch_nova']['char_set'] = 'utf8';
$db['branch_nova']['dbcollat'] = 'utf8_general_ci';
$db['branch_nova']['swap_pre'] = '';
$db['branch_nova']['autoinit'] = TRUE;
$db['branch_nova']['stricton'] = FALSE; 


$db['main_nova']['hostname'] = SRS_MAIN_BRANCH ;
$db['main_nova']['username'] = 'markuser';
$db['main_nova']['password'] = 'tseug';
$db['main_nova']['database'] = SRS_MAIN_DATABASE;
//$db['main_nova']['dbdriver'] = 'sqlsrv';
$db['main_nova']['dbdriver'] = 'mssql';

$db['main_nova']['dbprefix'] = '';
$db['main_nova']['pconnect'] = TRUE;
$db['main_nova']['db_debug'] = FALSE;
$db['main_nova']['cache_on'] = FALSE;
$db['main_nova']['cachedir'] = '';
$db['main_nova']['char_set'] = 'utf8';
$db['main_nova']['dbcollat'] = 'utf8_general_ci';
$db['main_nova']['swap_pre'] = '';
$db['main_nova']['autoinit'] = FALSE;
$db['main_nova']['stricton'] = FALSE; 

$db['main_nova_mysql'] = array(
	'dsn'	=> '',
	'hostname' => MYSQL_NOVA,
	'username' => 'root',
	'password' => MYSQL_PASS,
	'database' => MYSQL_NOVA_DATABASE,
	'dbdriver' => 'mysqli',
	'dbprefix' => '',
	'pconnect' => FALSE,
	'db_debug' => (ENVIRONMENT !== 'development'),
	'cache_on' => FALSE,
	'cachedir' => '',
	'char_set' => 'utf8',
	'dbcollat' => 'utf8_general_ci',
	'swap_pre' => '',
	'encrypt' => FALSE,
	'compress' => FALSE,
	'stricton' => FALSE,
	'failover' => array(),
	'save_queries' => TRUE
);

/*$db['datacenter']['hostname'] = '192.168.0.148';
$db['datacenter']['username'] = 'markuser';
$db['datacenter']['password'] = 'tseug';
$db['datacenter']['database'] = 'NEWDATACENTER';
$db['datacenter']['dbdriver'] = 'sqlsrv';*/

