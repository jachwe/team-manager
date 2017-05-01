<?php	

require 'vendor/autoload.php';
class_alias('\RedBeanPHP\R','R');

$base  = dirname($_SERVER['PHP_SELF']);

// Update request when we have a subdirectory
if (ltrim($base, '/'))
{
    $_SERVER['REQUEST_URI'] = substr($_SERVER['REQUEST_URI'], strlen($base));
}

$conf = include('config.php');

if($conf->dev){
	error_reporting(E_ALL);
	ini_set('display_errors', 1);
} else {
	error_reporting(0);
	ini_set('display_errors', 0);
}

ini_set('session.gc_maxlifetime', PHP_INT_MAX);

include('inc/common.php');
include('db/setup.php');

include('routes.php');

?>
