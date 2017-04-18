<?php	
session_start();
	
require 'vendor/autoload.php';
class_alias('\RedBeanPHP\R','R');

$conf = include('config.php');
include('inc/common.php');
include('db/setup.php');

include('routes.php');



?>
