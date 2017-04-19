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

include('inc/common.php');
include('db/setup.php');

include('routes.php');

?>
