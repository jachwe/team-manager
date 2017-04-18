<?php
	
$klein = new \Klein\Klein();

$klein->onError(function ($klein, $err_msg) {
        $klein->service()->flash($err_msg);
        $klein->service()->back();
});

$klein->respond('*', function ($request, $response, $service) {
	
	if( isPrivate() ){
		$service->startSession();
	}
	
	$service->layout('./layouts/front.phtml');
});

$klein->respond(array('POST','GET'),'/login/?', function ($request, $response, $service) {
	
	if( $request->method('post') ){
		$valid = checkPass($request->param('password'));
		if( $valid ){
			$_SESSION['loggedin'] = true;
			$response->redirect(getBase());
		}
	} else if($request->method('get')){
		$service->partial('./views/login.phtml');
	}
});

$klein->respond(array('POST','GET'),'/logout/?', function ($request, $response, $service) {
	
	unset($_SESSION['loggedin']);
	$response->redirect(getBase());
});

$klein->respond('/?', function ($request, $response, $service) {
	
	$response->redirect(getBase().'events');
});

$klein->with('/archive','./controller/archive.php');
$klein->with('/events?','./controller/events.php');
$klein->with('/extern','./controller/external.php');
$klein->with('/messages','./controller/messages.php');
$klein->with('/player','./controller/player.php');


$klein->dispatch();
