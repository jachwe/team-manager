<?php

$this->respond('/?', function ($request, $response, $service) {
	
		checkLogin();
	
		$service->links = R::findAll('link');
		$service->render('./views/external.phtml');
	});
	
$this->respond("POST", '/?', function ($request, $response, $service) {
	
		checkLogin();
	
		$link = R::dispense( 'link' );
		
		foreach($request->paramsPost() as $key => $val){
			$link[$key] = $val;
		}
		
		$link->date = time();
	

		R::store( $link );
		
		$service->flash("Der Link wurde gespeichert","success");
		$service->back();
	});